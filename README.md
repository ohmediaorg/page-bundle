# Overview

# Installation

Update `composer.json` by adding this to the `repositories` array:

```json
{
    "type": "vcs",
    "url": "https://github.com/ohmediaorg/page-bundle"
}
```

Then run `composer require ohmediaorg/page-bundle:dev-main`.

Import the routes in `config/routes.yaml`:

```yaml
oh_media_page:
    resource: '@OHMediaPageBundle/config/routes.yaml'
```

Run `php bin/console make:migration` then run the subsequent migration.

## Page Templates

Your page templates should all extend `@OHMediaPage/base.html.twig`.

Your `templates/base.html.twig` file would look like this:

```twig
{% extend '@OHMediaPage/base.html.twig' %}

{# you can optionally customize the opening <html> tag #}
{% block html_tag_open %}
<html lang="en" class="no-js">
{% endblock %}

{# you can optionally customize the meta content, which defaults to page_meta() #}
{% block meta %}
{% endblock %}

{# specify content to go after the frontend webpack style tag #}
{% block head_scripts %}
{% endblock %}

{# specify the main content of the template #}
{% block body %}
{% endblock %}

{# specify content to go after the frontend webpack JS tag #}
{% block body_scripts %}
{% endblock %}
```

Our base template processes the body content first in order to ensure the
page meta override functionality will work (see [Dynamic Content](#dynamic-content)).

**Note:** the `head_scripts` block should not contain any of the
tags found in `@OHMediaPage/meta.html.twig` and `@OHMediaMeta/meta.html.twig`.

## Managing Page Content

Each page template will need to be set up programatically:

```php
<?php

namespace App\Form;

use OHMedia\PageBundle\Form\Type\AbstractPageTemplateType;

class HomepageTemplateType extends AbstractPageTemplateType
{
    protected function buildFormContent()
    {
        $this
            ->addPageContentCheckbox('toggle')
            ->addPageContentChoice('color', [
                'choices' => [
                    // label => value
                    'Red' => 'red',
                    'Yellow' => 'yellow',
                    'Blue' => 'blue',
                ],
            ])
            ->addPageContentImage('image')
            ->addPageContentRow('row')
            ->addPageContentText('title')
            ->addPageContentTextarea('paragraph')
            ->addPageContentWysiwyg('wysiwyg')
        ;
    }

    public static function getTemplate(): string
    {
        // path relative to templates/
        // this template should ultimately extend "@OHMediaPage/base.html.twig"
        return 'pages/home.html.twig';
    }

    public static function getTemplateName(): string
    {
        // name shown in the backend UI
        return 'Homepage';
    }
}
```

Read more about each content area.

### Checkbox

The function `addPageContentCheckbox($name, $options)` is basically the same as
`$builder->add($name, CheckboxType::class, $options)`. It will always render a
checkbox with `required` false. You can pass in the custom option `checkbox_attr`
which is the same as `attr` on the `CheckboxType`.

Inside your template, you can use this content area like so:

```twig
{% if content_checkbox(name) %}
  {# ... #}
{% endif %}
```

### Choice

The function `addPageContentChoice($name, $options)` is basically the same as
`$builder->add($name, ChoiceType::class, $options)`. It will always render it
with `multiple` false. You can pass in the custom option `choice_attr` which is
the same as `attr` on `ChoiceType`.

Inside your template, you can use this content area like so:

```twig
{{ content_choice(name) }}
```

The value will be spit out as plain text. How you use it is up to you!

You can also check that the content exists before outputting it:

```twig
{% if content_choice_exists(name) %}
  {# ... #}
{% endif %}
```

### Image

The function `addPageContentImage($name, $options)` is basically the same as
`$builder->add($name, FileEntityType::class, $options)` (from the File bundle).
You can pass in the custom option `image_attr` which is the same as `attr` on
`FileEntityType`.

Inside your template, you can use this content area like so:

```twig
{{ content_image_tag(name) }}
```

This will output an `<img>` element.

You can also check that the content exists before outputting it:

```twig
{% if content_image_exists(name) %}
  <div class="image-wrapper">
    {{ content_image_tag(name) }}
  </div>
{% endif %}
```

You should always use `content_image_tag` for displaying an `<img>` element, but
if you only need the file path, you can use `content_image_path`:

```twig
<div style="background-image: url({{ content_image_path(name) }})"></div>
```

### Row

The function `addPageContentRow($name, $options)` is the most involved field.
It renders a `ChoiceType` and 3 `WysiwygType` fields. The `ChoiceType` is to
select a Layout (`one_column`, `two_column`, `three_column`, `sidebar_left`,
`sidebar_right`), and the `WysiwygType` fields are dynamically shown accordingly.

You can pass in the custom option `wysiwyg_attr` which is the same as `attr` on
the `WysiwygType` fields.

Inside your template, you can use this content area like so:

```twig
{{ content_row(name) }}
```

The output will include the minimum number of columns based on layout.

You can also check that the content exists before outputting it:

```twig
{% if content_row_exists(name) %}
  <div class="row-wrapper">
    {{ content_row(name) }}
  </div>
{% endif %}
```

#### Row Styles

You will need custom styles for the rows. Here's a starting point in Sass:

```sass
.page-content {
    &.page-content__row {
        display: grid;

        &.page-content__row--one_column {
            grid-template-columns: 1fr;
        }

        &.page-content__row--two_column {
            grid-template-columns: 1fr 1fr;
        }

        &.page-content__row--three_column {
            grid-template-columns: 1fr 1fr 1fr;
        }

        &.page-content__row--sidebar_left {
            grid-template-columns: 1fr 3fr;
        }

        &.page-content__row--sidebar_right {
            grid-template-columns: 3fr 1fr;
        }
    }
}
```

There is a generic column selector (`.page-content__col`) as well as a selector
for each of the 3 columns (`.page-content__col--1`, `.page-content__col--2`,
`.page-content__col--3`).

### Text

The function `addPageContentText($name, $options)` is basically the same as
`$builder->add($name, TextType::class, $options)`. You can pass in the custom
option `text_attr` which is the same as `attr` on the `TextType`.

Inside your template, you can use this content area like so:

```twig
<h2>{{ content_text(name) }}</h2>
```

The value will be spit out as plain text.

You can also check that the content exists before outputting it:

```twig
{% if content_text_exists(name) %}
  <h2>{{ content_text(name) }}</h2>
{% endif %}
```

### Textarea

The function `addPageContentTextarea($name, $options)` is basically the same as
`$builder->add($name, TextareaType::class, $options)`. You can pass in the custom
option `textarea_attr` which is the same as `attr` on the `TextareaType`.

Inside your template, you can use this content area like so:

```twig
<p>{{ content_textarea(name) }}</p>
```

The value will be spit out as plain text with newlines preserved as `<br>` tags.

You can also check that the content exists before outputting it:

```twig
{% if content_textarea_exists(name) %}
  <p>{{ content_textarea(name) }}</p>
{% endif %}
```

### Wysiwyg

The function `addPageContentWysiwyg($name, $options)` is basically the same as
`$builder->add($name, WysiwygType::class, $options)` (from the Wysiwyg bundle).
You can pass in the custom option `wysiwyg_attr` which is the same as `attr` on
`WysiwygType`.

Inside your template, you can use this content area like so:

```twig
{{ content_wysiwyg(name) }}
```

You can also check that the content exists before outputting it:

```twig
{% if content_wysiwyg_exists(name) %}
  {{ content_wysiwyg(name) }}
{% endif %}
```

## Dynamic Pages

Only dynamic pages can "catch" URLs. Pages will be flagged as dynamic or not
when a `PageRevision` is published.

A dynamic page can be freely moved around the page hierarchy. The dynamic
content doesn't care about the parent portion of the URL (eg. "/blog"), just the
section after it. In other words, the functionality would still work if the
dynamic page path was updated to "/about-us/blog".

To indicate a page is dynamic, its page template should extend
`AbstractDynamicPageTemplateType` instead. The Twig template corresponding with
this class should contain a dynamic Twig function.

### Dynamic Twig Function

See [EventsExtension](https://github.com/ohmediaorg/event-bundle/blob/main/src/Twig/EventsExtension.php)
in the event-bundle. This class is listening for the `DynamicPageEvent` in
order to query for the dynamic `Event` entity and populate the dynamic `Meta`
and `Breadcrumb` data before the page is rendered.

## Linking to the Parent Page

If you want to link back or reference the parent page from within your dynamic
content, you can use the `PageRenderer` service to get that page:

```php
$page = $this->pageRenderer->getCurrentPage();

$path = $page->getPath();
```

If you are not in the context of the page you want, you must know the template
you want to find. Then you can use the `PageRawQuery` service:

```php
use App\Form\Type\BlogPageType;

$pagePath = $this->pageRawQuery->getPathWithTemplate(BlogPageType::class);
```

## Sitemap URLs

To hook into the sitemap.xml functionality, create a service that extends
`OHMedia\PageBundle\Sitemap\AbstractSitemapUrlProvider`. You may need to
manually tag the service with `oh_media_page.sitemap_url_provider`.

See [EventSitemapUrlProvider](https://github.com/ohmediaorg/event-bundle/blob/main/src/Service/EventSitemapUrlProvider.php)
for an example with a dynamic shortcode.

See [WinnersSitemapUrlProvider](https://github.com/ohmediaorg/saskvlt-patron/blob/main/src/Service/WinnersSitemapUrlProvider.php)
for an example with a dynamic template.
