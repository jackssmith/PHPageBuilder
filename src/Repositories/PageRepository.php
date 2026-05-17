<?php

declare(strict_types=1);

namespace PHPageBuilder\Repositories;

use Exception;
use InvalidArgumentException;
use JsonException;
use PHPageBuilder\Contracts\PageContract;
use PHPageBuilder\Contracts\PageRepositoryContract;
use PHPageBuilder\Repositories\Contracts\TranslationRepositoryInterface;

/**
 * Class PageRepository
 *
 * Handles all database operations related to pages.
 * This repository is responsible for:
 * - Creating pages
 * - Updating pages
 * - Managing translations
 * - Updating JSON page data
 * - Cache invalidation
 * - Validation logic
 */
class PageRepository extends BaseRepository implements PageRepositoryContract
{
    /**
     * Required fields for page creation/update.
     */
    protected const REQUIRED_FIELDS = [
        'name',
        'layout',
    ];

    /**
     * Fields that require translations.
     */
    protected const TRANSLATABLE_FIELDS = [
        'title',
        'meta_title',
        'meta_description',
        'route',
    ];

    /**
     * Database table name.
     */
    protected string $table;

    /**
     * Page model class.
     */
    protected string $class;

    /**
     * Translation repository instance.
     */
    protected TranslationRepositoryInterface $translationRepository;

    /**
     * Foreign key used in translations table.
     */
    protected string $translationForeignKey;

    /**
     * PageRepository constructor.
     */
    public function __construct(
        ?TranslationRepositoryInterface $translationRepository = null
    ) {
        $this->table = phpb_config('page.table') ?: 'pages';

        parent::__construct();

        $this->class = phpb_instance('page');

        $this->translationForeignKey = (string) phpb_config(
            'page.translation.foreign_key'
        );

        $this->translationRepository = $translationRepository
            ?: new PageTranslationRepository();
    }

    /**
     * Create a new page.
     *
     * @throws Exception
     */
    public function create(array $data): PageContract
    {
        $this->validatePagePayload($data);

        /** @var mixed $page */
        $page = parent::create([
            'name'   => trim($data['name']),
            'layout' => trim($data['layout']),
        ]);

        if (! $page instanceof PageContract) {
            throw new Exception(
                'The created page instance must implement PageContract.'
            );
        }

        $this->syncTranslations($page, $data);

        $this->clearPageCache($page);

        return $page;
    }

    /**
     * Update an existing page.
     */
    public function update(PageContract $page, array $data): bool
    {
        $this->validatePagePayload($data);

        $this->syncTranslations($page, $data);

        $updated = parent::update($page, [
            'name'   => trim($data['name']),
            'layout' => trim($data['layout']),
        ]);

        $this->clearPageCache($page);

        return (bool) $updated;
    }

    /**
     * Update page JSON data.
     *
     * @throws JsonException
     */
    public function updatePageData(
        PageContract $page,
        array $data
    ): bool {
        $encodedData = json_encode(
            $data,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );

        $updated = parent::update($page, [
            'data' => $encodedData,
        ]);

        $this->clearPageCache($page);

        return (bool) $updated;
    }

    /**
     * Delete page by ID.
     */
    public function destroy(int $id): bool
    {
        $page = $this->findWithId($id);

        if ($page instanceof PageContract) {
            $this->clearPageCache($page);
        }

        return parent::destroy($id);
    }

    /**
     * Synchronize page translations.
     *
     * @throws InvalidArgumentException
     */
    protected function syncTranslations(
        PageContract $page,
        array $data
    ): void {
        $languages = phpb_active_languages();

        $this->validateTranslations($data, $languages);

        $this->deleteExistingTranslations($page);

        foreach ($languages as $locale => $language) {
            $this->createTranslationRecord(
                $page,
                $locale,
                $data
            );
        }
    }

    /**
     * Validate all required translation fields.
     *
     * @throws InvalidArgumentException
     */
    protected function validateTranslations(
        array $data,
        array $languages
    ): void {
        foreach (self::TRANSLATABLE_FIELDS as $field) {
            if (! isset($data[$field]) || ! is_array($data[$field])) {
                throw new InvalidArgumentException(
                    "Translation field '{$field}' must be an array."
                );
            }

            foreach ($languages as $locale => $language) {
                if (
                    ! isset($data[$field][$locale]) ||
                    ! is_string($data[$field][$locale])
                ) {
                    throw new InvalidArgumentException(
                        "Missing or invalid translation for '{$field}' in locale '{$locale}'."
                    );
                }
            }
        }
    }

    /**
     * Remove old translations before inserting new ones.
     */
    protected function deleteExistingTranslations(
        PageContract $page
    ): void {
        $this->translationRepository->destroyWhere(
            $this->translationForeignKey,
            $page->getId()
        );
    }

    /**
     * Create translation record.
     */
    protected function createTranslationRecord(
        PageContract $page,
        string $locale,
        array $data
    ): void {
        $this->translationRepository->create([
            $this->translationForeignKey => $page->getId(),
            'locale'                     => $locale,
            'title'                      => $data['title'][$locale],
            'meta_title'                 => $data['meta_title'][$locale],
            'meta_description'           => $data['meta_description'][$locale],
            'route'                      => $data['route'][$locale],
        ]);
    }

    /**
     * Validate required page payload fields.
     *
     * @throws InvalidArgumentException
     */
    protected function validatePagePayload(array $data): void
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (! array_key_exists($field, $data)) {
                throw new InvalidArgumentException(
                    "Required field '{$field}' is missing."
                );
            }

            if (! is_string($data[$field])) {
                throw new InvalidArgumentException(
                    "Field '{$field}' must be a string."
                );
            }

            if (trim($data[$field]) === '') {
                throw new InvalidArgumentException(
                    "Field '{$field}' cannot be empty."
                );
            }
        }
    }

    /**
     * Invalidate page cache safely.
     */
    protected function clearPageCache(PageContract $page): void
    {
        if (method_exists($page, 'invalidateCache')) {
            $page->invalidateCache();
        }
    }

    /**
     * Determine whether a page exists.
     */
    public function exists(int $id): bool
    {
        return $this->findWithId($id) instanceof PageContract;
    }

    /**
     * Get all active languages.
     */
    protected function getActiveLanguages(): array
    {
        return phpb_active_languages();
    }

    /**
     * Get repository table name.
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get page model class.
     */
    public function getModelClass(): string
    {
        return $this->class;
    }
}
