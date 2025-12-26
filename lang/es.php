<?php

return [
    'auth' => [
        'title' => 'Webuilder Manager',
        'username' => 'Usuario',
        'password' => 'Contraseña',
        'login-button' => 'Iniciar sesión',
        'invalid-credentials' => 'Usuario o contraseña incorrectos',
    ],

    'website-manager' => [
        'title' => 'Webuilder Manager',
        'logout' => 'Cerrar sesión',
        'pages' => 'Páginas',
        'menus' => 'Menús',
        'name' => 'Nombre',
        'page-title' => 'Título de la página',
        'page-meta-title' => 'Meta título',
        'page-meta-description' => 'Meta descripción',
        'route' => 'URL',
        'layout' => 'Diseño',
        'actions' => 'Acciones',
        'view' => 'Ver',
        'edit' => 'Editar',
        'remove' => 'Eliminar',
        'add-new-page' => 'Añadir página',
        'edit-page' => 'Editar página',
        'back' => 'Volver',
        'save-changes' => 'Guardar cambios',
        'theme' => 'Tema',
        'visible-in-page-overview' => 'Visible en el listado de páginas',
        'page-created' => 'La página se ha creado correctamente',
        'page-updated' => 'La página se ha actualizado correctamente',
        'page-deleted' => 'La página se ha eliminado correctamente',

        'settings' => 'Configuración',
        'website-languages' => 'Idiomas del sitio web',
        'languages-selector-placeholder' => 'Selecciona uno o varios idiomas',
        'save-settings' => 'Guardar configuración',
        'settings-updated' => 'La configuración se ha actualizado correctamente',
        'pagebuilder-block-images' => 'Listado de bloques',
        'render-thumbs' => 'Mostrar miniaturas',
    ],

    'pagebuilder' => [
        'filter-placeholder' => 'Filtrar',
        'loading-text' => 'Cargando Webuilder…',
        'style-no-element-selected' => 'Selecciona un elemento para modificar su estilo.',
        'trait-no-element-selected' => 'Selecciona un elemento para modificar sus atributos.',
        'trait-settings' => 'Configuración',
        'default-category' => 'General',
        'view-blocks' => 'Bloques',
        'view-settings' => 'Configuración',
        'view-style-manager' => 'Gestor de estilos',
        'save-page' => 'Guardar',
        'view-page' => 'Ver página',
        'go-back' => 'Volver',
        'page' => 'Página',
        'page-content' => 'Contenido de la página',
        'toastr-changes-saved' => 'Los cambios se han guardado correctamente',
        'toastr-saving-failed' => 'Error al guardar los cambios',
        'toastr-component-update-failed' => 'Error al actualizar el componente',
        'toastr-switching-language-failed' => 'Error al cambiar el idioma',
        'yes' => 'Sí',
        'no' => 'No',

        'trait-manager' => [
            'link' => [
                'text' => 'Texto',
                'target' => '¿Abrir en una nueva pestaña?',
                'tooltip' => 'Información',
            ],
            'image' => [
                'title' => 'Título al pasar el cursor',
                'alt' => 'Texto alternativo',
            ],
            'no-settings' => 'Este bloque no tiene opciones de configuración.',
        ],

        'selector-manager' => [
            'label' => 'Clases CSS',
            'states-label' => 'Estado del diseño',
            'selected-label' => 'Seleccionado',
            'state-hover' => 'Hover',
            'state-active' => 'Activo',
            'state-nth' => 'Par / Impar',
        ],

        'style-manager' => [
            'sectors' => [
                'position' => 'Posición',
                'background' => 'Fondo',
                'advanced' => 'Avanzado',
            ],
            'properties' => [
                'position' => [
                    'width' => 'Ancho',
                    'min-width' => 'Ancho mínimo',
                    'max-width' => 'Ancho máximo',
                    'height' => 'Alto',
                    'min-height' => 'Alto mínimo',
                    'max-height' => 'Alto máximo',
                    'padding' => [
                        'name' => 'Relleno',
                        'properties' => [
                            'padding-top' => 'Superior',
                            'padding-right' => 'Derecha',
                            'padding-bottom' => 'Inferior',
                            'padding-left' => 'Izquierda',
                        ],
                    ],
                    'margin' => [
                        'name' => 'Margen',
                        'properties' => [
                            'margin-top' => 'Superior',
                            'margin-right' => 'Derecha',
                            'margin-bottom' => 'Inferior',
                            'margin-left' => 'Izquierda',
                        ],
                    ],
                    'text-align' => [
                        'name' => 'Alineación del texto',
                    ],
                ],
                'background' => [
                    'background-color' => 'Color de fondo',
                    'background' => 'Imagen de fondo',
                ],
            ],
        ],

        'asset-manager' => [
            'modal-title' => 'Seleccionar imagen',
            'drop-files' => 'Arrastra los archivos aquí o haz clic para subirlos',
            'add-image' => 'Añadir imagen',
        ],
    ],

    'languages' => [
        'en' => 'Inglés',
        'nl' => 'Neerlandés',
        'es' => 'Español',
        'it' => 'Italiano',
        'fr' => 'Francés',
        'de' => 'Alemán',
    ],
];
