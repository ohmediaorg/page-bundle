# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [[220d27a](https://github.com/ohmediaorg/page-bundle/commit/220d27a4b500c9c4fef930d111b3dd6ed32e5916)] - 2025-10-02

### Added

### Changed

### Fixed

- make sure page paths with trailing slashes are not 404

## [[7d2fd48](https://github.com/ohmediaorg/page-bundle/commit/7d2fd48ae2029f4b658b9c54cfcb2d57647c19aa)] - 2025-05-16

### Added

### Changed

- a page with children can be designated as dropdown_only meaning it will
automatically redirect to an appropriate child page

### Fixed

## [[77c5791](https://github.com/ohmediaorg/page-bundle/commit/77c57912e2aada6678f2fe3c1d78b0195332d60f)] - 2025-05-06

### Added

- `get_page_nav` Twig function to retrieve page nav from a certain parent page

### Changed

- moved page navigation logic from template to Twig extension

### Fixed

## [[ef0ff58](https://github.com/ohmediaorg/page-bundle/commit/ef0ff5847a0b7b8cfa266ffeeaa14b1d384f4427)] - 2025-05-06

Dynamic pages that extended `AbstractPageTemplateType` must now extend
`AbstractDynamicPageTemplateType`.

### Added

- `AbstractDynamicPageTemplateType` for indicating dynamic page templates

### Changed

- dynamic page templates are shifted to the bottom of the selection
- only developer users can select dynamic templates
- only developer users can change a dynamic page's template
- replace page content shortcode searching with template matching

### Fixed

- `label` option is now used for image page content fields
- choice page content fields are no longer visually wrapped with a fieldset
- page slugs are now visually broken up by `/` characters on listing to prevent
long horizontal content

## [[0ed4ac7](https://github.com/ohmediaorg/page-bundle/commit/0ed4ac7e644c9ba746aad4851ccebf41eee528c3)] - 2025-05-06

### Added

- ability to specify which types of users can access a locked page
- common actions dropdown for Edit, Navigation, and SEO pages

### Changed

### Fixed
