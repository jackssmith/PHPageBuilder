<?php

namespace PHPageBuilder\Repositories;

use PHPageBuilder\Contracts\PageContract;
use PHPageBuilder\Contracts\PageRepositoryContract;
use Exception;
use InvalidArgumentException;

class PageRepository extends BaseRepository implements PageRepositoryContract
{
    protected const REQUIRED_PAGE_FIELDS = ['name', 'layout'];
    protected const TRANSLATABLE_FIELDS = [
        'title',
        'meta_title',
        'meta_description',
        'route',
    ];

    /**
     * The pages database table.
     *
     * @var string
     */
    protected string $table;

    /**
     * The class that represents each page.
     *
     * @var string
     */
    protected string $class;

    public function __construct()
    {
        $this->table = phpb_config('page.table') ?: 'pages';
        parent::__construct();
        $this->class = phpb_instance('page');
    }

    /**
     * Create a new page.
     *
     * @throws Exception
     */
    public function create(array $data): PageContract
    {
        $this->validatePageFields($data);

        $page = parent::create([
            'name'   => $data['name'],
            'layout' => $data['layout'],
        ]);

        if (! $page instanceof PageContract) {
            throw new Exception('Created page does not implement PageContract.');
        }

        $this->replaceTranslations($page, $data);

        return $page;
    }

    /**
     * Update the given page.
     */
    public function update(PageContract $page, array $data): bool
    {
        $this->validatePageFields($data);

        $this->replaceTranslations($page, $data);

        $updated = parent::update($page, [
            'name'   => $data['name'],
            'layout' => $data['layout'],
        ]);

        $page->invalidateCache();

        return (bool) $updated;
    }

    /**
     * Replace the translations of the given page.
     *
     * @throws InvalidArgumentException
     */
    protected function replaceTranslations(PageContract $page, array $data): void
    {
        $activeLanguages = phpb_active_languages();

        foreach (self::TRANSLATABLE_FIELDS as $field) {
            foreach ($activeLanguages as $locale => $_) {
                if (! isset($data[$field][$locale])) {
                    throw new InvalidArgumentException(
                        "Missing translation for field '{$field}' and locale '{$locale}'."
                    );
                }
            }
        }

        $translationRepository = new PageTranslationRepository();
        $foreignKey = phpb_config('page.translation.foreign_key');

        $translationRepository->destroyWhere($foreignKey, $page->getId());

        foreach ($activeLanguages as $locale => $_) {
            $translationRepository->create([
                $foreignKey        => $page->getId(),
                'locale'           => $locale,
                'title'            => $data['title'][$locale],
                'meta_title'       => $data['meta_title'][$locale],
                'meta_description' => $data['meta_description'][$locale],
                'route'            => $data['route'][$locale],
            ]);
        }
    }

    /**
     * Update structured page data (JSON).
     */
    public function updatePageData(PageContract $page, array $data): bool
    {
        $updated = parent::update($page, [
            'data' => json_encode($data, JSON_THROW_ON_ERROR),
        ]);

        $page->invalidateCache();

        return (bool) $updated;
    }

    /**
     * Remove the given page from the database.
     */
    public function destroy(int $id): bool
    {
        $page = $this->findWithId($id);

        if ($page instanceof PageContract) {
            $page->invalidateCache();
        }

        return parent::destroy($id);
    }

    /**
     * Validate required page fields.
     */
    protected function validatePageFields(array $data): void
    {
        foreach (self::REQUIRED_PAGE_FIELDS as $field) {
            if (! isset($data[$field]) || ! is_string($data[$field])) {
                throw new InvalidArgumentException("Invalid or missing field: {$field}");
            }
        }
    }
}
