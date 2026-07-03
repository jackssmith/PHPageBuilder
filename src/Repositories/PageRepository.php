<?php

declare(strict_types=1);

namespace PHPageBuilder\Repositories;

use Exception;
use InvalidArgumentException;
use JsonException;
use PHPageBuilder\Contracts\PageContract;
use PHPageBuilder\Contracts\PageRepositoryContract;
use PHPageBuilder\Repositories\Contracts\TranslationRepositoryInterface;

class PageRepository extends BaseRepository implements PageRepositoryContract
{
    protected const REQUIRED_FIELDS = [
        'name',
        'layout',
    ];

    protected const TRANSLATABLE_FIELDS = [
        'title',
        'meta_title',
        'meta_description',
        'route',
    ];

    protected string $table;

    protected string $class;

    protected string $translationForeignKey;

    protected TranslationRepositoryInterface $translationRepository;

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
            ?? new PageTranslationRepository();
    }

    /**
     * @throws Exception
     */
    public function create(array $data): PageContract
    {
        $this->validatePagePayload($data);

        /** @var mixed $page */
        $page = parent::create($this->extractPageData($data));

        if (! $page instanceof PageContract) {
            throw new Exception(
                'Created page must implement PageContract.'
            );
        }

        $this->syncTranslations($page, $data);
        $this->invalidateCache($page);

        return $page;
    }

    public function update(PageContract $page, array $data): bool
    {
        $this->validatePagePayload($data);

        $this->syncTranslations($page, $data);

        $updated = parent::update(
            $page,
            $this->extractPageData($data)
        );

        $this->invalidateCache($page);

        return (bool) $updated;
    }

    /**
     * @throws JsonException
     */
    public function updatePageData(
        PageContract $page,
        array $data
    ): bool {
        $updated = parent::update($page, [
            'data' => json_encode(
                $data,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            ),
        ]);

        $this->invalidateCache($page);

        return (bool) $updated;
    }

    public function destroy(int $id): bool
    {
        $page = $this->findWithId($id);

        if ($page instanceof PageContract) {
            $this->invalidateCache($page);
        }

        return parent::destroy($id);
    }

    public function exists(int $id): bool
    {
        return $this->findWithId($id) instanceof PageContract;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getModelClass(): string
    {
        return $this->class;
    }

    protected function extractPageData(array $data): array
    {
        return [
            'name' => trim($data['name']),
            'layout' => trim($data['layout']),
        ];
    }

    protected function syncTranslations(
        PageContract $page,
        array $data
    ): void {
        $languages = phpb_active_languages();

        $this->validateTranslations($data, $languages);

        $this->translationRepository->destroyWhere(
            $this->translationForeignKey,
            $page->getId()
        );

        foreach (array_keys($languages) as $locale) {
            $translation = [
                $this->translationForeignKey => $page->getId(),
                'locale' => $locale,
            ];

            foreach (self::TRANSLATABLE_FIELDS as $field) {
                $translation[$field] = $data[$field][$locale];
            }

            $this->translationRepository->create($translation);
        }
    }

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

            foreach (array_keys($languages) as $locale) {
                if (
                    ! isset($data[$field][$locale]) ||
                    ! is_string($data[$field][$locale])
                ) {
                    throw new InvalidArgumentException(
                        "Missing translation for '{$field}' ({$locale})."
                    );
                }
            }
        }
    }

    protected function validatePagePayload(array $data): void
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (! array_key_exists($field, $data)) {
                throw new InvalidArgumentException(
                    "Missing required field '{$field}'."
                );
            }

            if (
                ! is_string($data[$field]) ||
                trim($data[$field]) === ''
            ) {
                throw new InvalidArgumentException(
                    "Field '{$field}' must be a non-empty string."
                );
            }
        }
    }

    protected function invalidateCache(PageContract $page): void
    {
        if (method_exists($page, 'invalidateCache')) {
            $page->invalidateCache();
        }
    }
}
