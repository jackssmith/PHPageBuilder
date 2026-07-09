<?php

return [

    /*
     |--------------------------------------------------------------------------
     | General Settings
     |--------------------------------------------------------------------------
     |
     | Configure the core PageBuilder options.
     |
     | If PHPageBuilder is installed via Composer, set the assets URL to:
     |
     |     /vendor/hansschouten/phpagebuilder/dist
     |
     */
    'general' => [
        'base_url'    => 'http://localhost',
        'language'    => 'en',
        'assets_url'  => '/assets',
        'uploads_url' => '/uploads',
    ],

    /*
     |--------------------------------------------------------------------------
     | Storage Settings
     |--------------------------------------------------------------------------
     |
     | Configure database connectivity and file storage locations.
     |
     */
    'storage' => [
        'use_database' => true,

        'database' => [
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'database'  => '',
            'username'  => '',
            'password'  => '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ],

        'uploads_folder' => __DIR__ . '/uploads',
    ],

    /*
     |--------------------------------------------------------------------------
     | Authentication Settings
     |--------------------------------------------------------------------------
     |
     | Configure authentication for the PageBuilder administration panel.
     | The default authentication class validates the credentials defined
     | below. Replace the authentication class to integrate with your own
     | authentication system.
     |
     */
    'auth' => [
        'use_login' => true,
        'class'     => PHPageBuilder\Modules\Auth\Auth::class,
        'url'       => '/admin/auth',
        'username'  => 'admin',
        'password'  => 'changethispassword',
    ],

    /*
     |--------------------------------------------------------------------------
     | Website Manager Settings
     |--------------------------------------------------------------------------
     |
     | Configure the built-in Website Manager used to create, edit, and
     | organize website pages.
     |
     */
    'website_manager' => [
        'use_website_manager' => true,
        'class'               => PHPageBuilder\Modules\WebsiteManager\WebsiteManager::class,
        'url'                 => '/admin',
    ],

    /*
     |--------------------------------------------------------------------------
     | Website Settings Provider
     |--------------------------------------------------------------------------
     |
     | Defines the class responsible for storing and retrieving website-wide
     | configuration values.
     |
     */
    'setting' => [
        'class' => PHPageBuilder\Setting::class,
    ],

    /*
     |--------------------------------------------------------------------------
     | PageBuilder Settings
     |--------------------------------------------------------------------------
     |
     | Configure the visual page editor. The default implementation is based
     | on GrapesJS.
     |
     */
    'pagebuilder' => [
        'class' => PHPageBuilder\Modules\GrapesJS\PageBuilder::class,
        'url'   => '/admin/pagebuilder',

        'actions' => [
            'back' => '/admin',
        ],
    ],

    /*
     |--------------------------------------------------------------------------
     | Page Model Settings
     |--------------------------------------------------------------------------
     |
     | Configure the page model, database tables, and translation model used
     | for multilingual content.
     |
     */
    'page' => [
        'class' => PHPageBuilder\Page::class,
        'table' => 'pages',

        'translation' => [
            'class'       => PHPageBuilder\PageTranslation::class,
            'table'       => 'page_translations',
            'foreign_key' => 'page_id',
        ],
    ],

    /*
     |--------------------------------------------------------------------------
     | Cache Settings
     |--------------------------------------------------------------------------
     |
     | Enable page caching to improve performance by skipping block parsing
     | for previously generated pages.
     |
     | Pages containing blocks with caching disabled will never be cached.
     | Cached pages are automatically invalidated whenever they are updated
     | in the PageBuilder.
     |
     */
    'cache' => [
        'enabled' => false,
        'folder'  => __DIR__ . '/cache',
        'class'   => PHPageBuilder\Cache::class,
    ],

    /*
     |--------------------------------------------------------------------------
     | Theme Settings
     |--------------------------------------------------------------------------
     |
     | Configure the active theme and the location of theme resources.
     |
     | Each theme resides in its own directory and may contain blocks,
     | views, controllers, and optional models.
     |
     */
    'theme' => [
        'class'        => PHPageBuilder\Theme::class,
        'folder'       => __DIR__ . '/themes',
        'folder_url'   => '/themes',
        'active_theme' => 'demo',
    ],

    /*
     |--------------------------------------------------------------------------
     | Routing Settings
     |--------------------------------------------------------------------------
     |
     | Defines how incoming requests are resolved to website pages.
     |
     */
    'router' => [
        'class' => PHPageBuilder\Modules\Router\DatabasePageRouter::class,
    ],

    /*
     |--------------------------------------------------------------------------
     | Class Replacements
     |--------------------------------------------------------------------------
     |
     | Replace built-in classes with custom implementations.
     |
     | The key is the original class, and the value is the replacement class.
     | Custom classes should extend the original implementation to maintain
     | compatibility.
     |
     | Example:
     |
     | PHPageBuilder\UploadedFile::class => App\PageBuilder\UploadedFile::class
     |
     */
    'class_replacements' => [
    ],

];
