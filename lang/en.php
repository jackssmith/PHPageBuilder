<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'title' => 'Website Manager',
        'username' => 'Username',
        'email' => 'Email address',
        'password' => 'Password',
        'confirm-password' => 'Confirm password',
        'login-button' => 'Login',
        'logout-button' => 'Logout',
        'remember-me' => 'Remember me',
        'forgot-password' => 'Forgot password?',
        'invalid-credentials' => 'Invalid username or password',
        'login-success' => 'Successfully logged in',
        'logout-success' => 'Successfully logged out',
        'session-expired' => 'Your session has expired. Please log in again.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Website Manager
    |--------------------------------------------------------------------------
    */
    'website-manager' => [
        'title' => 'Website Manager',
        'dashboard' => 'Dashboard',
        'logout' => 'Logout',

        'pages' => 'Pages',
        'menus' => 'Menus',
        'media' => 'Media Library',
        'settings' => 'Settings',

        'name' => 'Name',
        'slug' => 'Slug',
        'page-title' => 'Page menu title',
        'page-meta-title' => 'Page meta title',
        'page-meta-description' => 'Page meta description',
        'route' => 'URL',
        'layout' => 'Layout',
        'theme' => 'Theme',

        'status' => 'Status',
        'published' => 'Published',
        'draft' => 'Draft',
        'visibility' => 'Visibility',
        'visible-in-page-overview' => 'Visible in page overview',

        'actions' => 'Actions',
        'view' => 'View',
        'edit' => 'Edit',
        'duplicate' => 'Duplicate',
        'remove' => 'Remove',

        'add-new-page' => 'Add new page',
        'edit-page' => 'Edit page',
        'back' => 'Back',
        'save-changes' => 'Save changes',
        'cancel' => 'Cancel',

        'page-created' => 'Page successfully created',
        'page-updated' => 'Page successfully updated',
        'page-deleted' => 'Page successfully removed',
        'page-duplicated' => 'Page successfully duplicated',

        'delete-confirmation' => 'Are you sure you want to delete this page?',
        'unsaved-changes-warning' => 'You have unsaved changes. Continue?',

        /*
        | Settings
        */
        'website-languages' => 'Website languages',
        'languages-selector-placeholder' => 'Select one or more languages',
        'save-settings' => 'Save settings',
        'settings-updated' => 'Settings successfully updated',
        'settings-save-failed' => 'Failed to save settings',

        'pagebuilder-block-images' => 'Pagebuilder block thumbnails',
        'render-thumbs' => 'Render thumbnails',
    ],

    /*
    |--------------------------------------------------------------------------
    | Page Builder
    |--------------------------------------------------------------------------
    */
    'pagebuilder' => [
        'filter-placeholder' => 'Filter blocks...',
        'loading-text' => 'Loading page builder…',

        'view-blocks' => 'Blocks',
        'view-settings' => 'Settings',
        'view-style-manager' => 'Style Manager',

        'save-page' => 'Save',
        'saving' => 'Saving...',
        'view-page' => 'View page',
        'go-back' => 'Back',
        'page' => 'Page',
        'page-content' => 'Page contents',

        'style-no-element-selected' => 'Select an element to modify its style.',
        'trait-no-element-selected' => 'Select an element to modify its attributes.',

        'toastr-changes-saved' => 'Changes saved successfully',
        'toastr-saving-failed' => 'Error while saving changes',
        'toastr-component-update-failed' => 'Error while reloading component',
        'toastr-switching-language-failed' => 'Error while switching language',

        'confirm-leave' => 'Leave page without saving?',
        'yes' => 'Yes',
        'no' => 'No',

        /*
        | Trait Manager
        */
        'trait-manager' => [
            'settings' => 'Settings',
            'link' => [
                'text' => 'Link text',
                'href' => 'URL',
                'target' => 'Open in new tab?',
                'tooltip' => 'Tooltip',
            ],
            'image' => [
                'title' => 'Title (mouse-over)',
                'alt' => 'Alternative text',
                'lazy' => 'Lazy load image',
            ],
            'video' => [
                'source' => 'Video source URL',
                'autoplay' => 'Autoplay',
                'controls' => 'Show controls',
            ],
            'no-settings' => 'This block does not have any settings.',
        ],

        /*
        | Selector Manager
        */
        'selector-manager' => [
            'label' => 'CSS classes',
            'states-label' => 'State',
            'selected-label' => 'Selected',
            'state-hover' => 'Hover',
            'state-active' => 'Active',
            'state-focus' => 'Focus',
            'state-nth' => 'Even / Odd',
        ],

        /*
        | Style Manager
        */
        'style-manager' => [
            'sectors' => [
                'position' => 'Position',
                'background' => 'Background',
                'typography' => 'Typography',
                'advanced' => 'Advanced',
            ],
            'properties' => [
                'position' => [
                    'width' => 'Width',
                    'min-width' => 'Minimum width',
                    'max-width' => 'Maximum width',
                    'height' => 'Height',
                    'min-height' => 'Minimum height',
                    'max-height' => 'Maximum height',

                    'padding' => [
                        'name' => 'Padding',
                        'properties' => [
                            'padding-top' => 'Top',
                            'padding-right' => 'Right',
                            'padding-bottom' => 'Bottom',
                            'padding-left' => 'Left',
                        ],
                    ],
                    'margin' => [
                        'name' => 'Margin',
                        'properties' => [
                            'margin-top' => 'Top',
                            'margin-right' => 'Right',
                            'margin-bottom' => 'Bottom',
                            'margin-left' => 'Left',
                        ],
                    ],
                    'text-align' => [
                        'name' => 'Text align',
                    ],
                ],
                'background' => [
                    'background-color' => 'Background color',
                    'background-image' => 'Background image',
                    'background-repeat' => 'Repeat',
                    'background-size' => 'Size',
                ],
            ],
        ],

        /*
        | Asset Manager
        */
        'asset-manager' => [
            'modal-title' => 'Select image',
            'drop-files' => 'Drop files here or click to upload',
            'uploading' => 'Uploading...',
            'add-image' => 'Add image',
            'remove-image' => 'Remove image',
            'upload-failed' => 'Upload failed',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Languages
    |--------------------------------------------------------------------------
    */
    'languages' => [
        'en' => 'English',
        'nl' => 'Dutch',
        'es' => 'Spanish',
        'it' => 'Italian',
        'fr' => 'French',
        'de' => 'German',
    ],

    /*
    |--------------------------------------------------------------------------
    | Common / Global
    |--------------------------------------------------------------------------
    */
    'common' => [
        'search' => 'Search',
        'loading' => 'Loading...',
        'error' => 'An error occurred',
        'success' => 'Success',
        'confirm' => 'Confirm',
        'close' => 'Close',
    ],
];
