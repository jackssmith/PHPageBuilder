<?php

return [

    'general' => [
        'yes' => 'Ja',
        'no' => 'Nee',
        'save' => 'Opslaan',
        'cancel' => 'Annuleren',
        'delete' => 'Verwijderen',
        'edit' => 'Bewerken',
        'view' => 'Bekijken',
        'back' => 'Terug',
        'actions' => 'Acties',
        'loading' => 'Laden...',
        'search' => 'Zoeken',
        'confirm-delete' => 'Weet je zeker dat je dit item wilt verwijderen?',
        'success' => 'Actie succesvol uitgevoerd',
        'error' => 'Er is een fout opgetreden',
    ],

    'auth' => [
        'title' => 'Websitebeheer',
        'username' => 'Gebruikersnaam',
        'password' => 'Wachtwoord',
        'remember-me' => 'Onthoud mij',
        'forgot-password' => 'Wachtwoord vergeten?',
        'login-button' => 'Inloggen',
        'logout' => 'Uitloggen',
        'invalid-credentials' => 'De ingevulde logingegevens zijn onjuist.',
        'login-success' => 'Succesvol ingelogd.',
        'logged-out' => 'Je bent uitgelogd.',
    ],

    'website-manager' => [
        'title' => 'Websitebeheer',
        'dashboard' => 'Dashboard',

        'pages' => 'Pagina\'s',
        'add-new-page' => 'Nieuwe pagina toevoegen',
        'edit-page' => 'Pagina bewerken',
        'page-details' => 'Pagina details',

        'name' => 'Naam',
        'page-title' => 'Pagina titel',
        'page-meta-title' => 'SEO titel',
        'page-meta-description' => 'SEO omschrijving',
        'page-meta-keywords' => 'SEO zoekwoorden',
        'route' => 'URL',
        'slug' => 'Slug',
        'layout' => 'Pagina layout',
        'theme' => 'Thema',
        'status' => 'Status',
        'published' => 'Gepubliceerd',
        'draft' => 'Concept',
        'visibility' => 'Zichtbaarheid',
        'visible-in-page-overview' => 'Zichtbaar in pagina overzicht',
        'publish-date' => 'Publicatiedatum',

        'menus' => 'Menu\'s',
        'menu-title' => 'Menu titel',
        'menu-location' => 'Menu locatie',

        'save-changes' => 'Wijzigingen opslaan',
        'page-created' => 'Nieuwe pagina succesvol aangemaakt.',
        'page-updated' => 'Pagina succesvol bijgewerkt.',
        'page-deleted' => 'Pagina succesvol verwijderd.',
        'page-not-found' => 'Pagina niet gevonden.',

        'settings' => 'Instellingen',
        'website-languages' => 'Website talen',
        'languages-selector-placeholder' => 'Selecteer één of meerdere talen',
        'default-language' => 'Standaard taal',
        'save-settings' => 'Instellingen opslaan',
        'settings-updated' => 'Instellingen succesvol opgeslagen.',

        'pagebuilder-block-images' => 'Pagebuilder blok afbeeldingen',
        'render-thumbs' => 'Blokafbeeldingen genereren',
        'cache-cleared' => 'Cache succesvol geleegd.',
    ],

    'pagebuilder' => [
        'pagebuilder-title' => 'Pagina bewerken',
        'filter-placeholder' => 'Filter blokken...',
        'loading-text' => 'Bewerkmodus wordt geladen...',
        'page' => 'Pagina',
        'page-content' => 'Pagina inhoud',

        'view-blocks' => 'Blokken',
        'view-settings' => 'Instellingen',
        'view-style-manager' => 'Opmaak',
        'view-layers' => 'Lagen',
        'view-traits' => 'Eigenschappen',

        'save-page' => 'Pagina opslaan',
        'save-and-exit' => 'Opslaan en sluiten',
        'view-page' => 'Bekijk pagina',
        'go-back' => 'Terug naar overzicht',

        'unsaved-changes' => 'Er zijn niet-opgeslagen wijzigingen.',
        'confirm-leave' => 'Weet je zeker dat je deze pagina wilt verlaten zonder op te slaan?',

        'toastr-changes-saved' => 'Wijzigingen succesvol opgeslagen.',
        'toastr-saving-failed' => 'Er is een fout opgetreden bij het opslaan.',
        'toastr-component-update-failed' => 'Fout bij het vernieuwen van het blok.',
        'toastr-switching-language-failed' => 'Fout bij het wisselen van taal.',

        'style-no-element-selected' => 'Selecteer een element om de opmaak aan te passen.',
        'trait-no-element-selected' => 'Selecteer een element om de eigenschappen aan te passen.',

        'trait-manager' => [
            'no-settings' => 'Dit blok heeft geen instellingen.',

            'link' => [
                'text' => 'Linktekst',
                'url' => 'URL',
                'target' => 'Openen in nieuw tabblad?',
                'tooltip' => 'Tooltip tekst',
                'nofollow' => 'Gebruik nofollow?',
            ],

            'image' => [
                'title' => 'Titel (tooltip)',
                'alt' => 'Alternatieve tekst (voor SEO en toegankelijkheid)',
                'width' => 'Breedte',
                'height' => 'Hoogte',
            ],
        ],

        'selector-manager' => [
            'label' => 'CSS classes',
            'states-label' => 'Opmaak voor',
            'selected-label' => 'Geselecteerd',
            'state-hover' => 'Muis over element',
            'state-active' => 'Actief element',
            'state-focus' => 'Focus',
            'state-nth' => 'Even / oneven elementen',
        ],

        'style-manager' => [
            'sectors' => [
                'position' => 'Positie & Afmetingen',
                'typography' => 'Typografie',
                'background' => 'Achtergrond',
                'border' => 'Rand',
                'advanced' => 'Geavanceerd',
            ],

            'properties' => [
                'position' => [
                    'width' => 'Breedte',
                    'min-width' => 'Minimale breedte',
                    'max-width' => 'Maximale breedte',
                    'height' => 'Hoogte',
                    'min-height' => 'Minimale hoogte',
                    'max-height' => 'Maximale hoogte',
                    'text-align' => [
                        'name' => 'Tekstuitlijning',
                    ],
                ],

                'typography' => [
                    'font-family' => 'Lettertype',
                    'font-size' => 'Lettergrootte',
                    'font-weight' => 'Letterdikte',
                    'line-height' => 'Regelhoogte',
                    'color' => 'Tekstkleur',
                ],

                'background' => [
                    'background-color' => 'Achtergrondkleur',
                    'background-image' => 'Achtergrondafbeelding',
                    'background-size' => 'Achtergrondgrootte',
                    'background-position' => 'Achtergrondpositie',
                    'background-repeat' => 'Achtergrond herhalen',
                ],
            ],
        ],

        'asset-manager' => [
            'modal-title' => 'Selecteer afbeelding',
            'drop-files' => 'Klik hier of sleep bestanden hierheen',
            'add-image' => 'Afbeelding uploaden',
            'delete-image' => 'Afbeelding verwijderen',
            'image-uploaded' => 'Afbeelding succesvol geüpload.',
            'upload-failed' => 'Uploaden van afbeelding mislukt.',
        ],
    ],

    'validation' => [
        'required' => 'Dit veld is verplicht.',
        'max-length' => 'Dit veld mag maximaal :max tekens bevatten.',
        'invalid-url' => 'Voer een geldige URL in.',
        'unique' => 'Deze waarde bestaat al.',
    ],

    'languages' => [
        'en' => 'Engels',
        'nl' => 'Nederlands',
        'es' => 'Spaans',
        'it' => 'Italiaans',
        'fr' => 'Frans',
        'de' => 'Duits',
    ],
];
