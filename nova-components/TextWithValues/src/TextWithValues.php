<?php

namespace Materialize\TextWithValues;

use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class TextWithValues extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'text-with-values';

    /**
     * Indicates if the field is used to manipulate JSON.
     *
     * @var bool
     */
    public $json = false;
    public $visible = true;

    /**
     * The JSON encoding options.
     *
     * @var int|null
     */
    public $jsonOptions;

    /**
     * Indicates if the element should be shown on the index view.
     *
     * @var bool
     */
    public $showOnIndex = false;

    /**
     * Indicates the visual height of the Code editor.
     *
     * @var string|int
     */
    public $height = 300;

    public $buttons = [];

    public function withLinkButton()
    {
        return $this->withButton('Link', '(.*)', '<a href="{cursor!}">$1</a>');
    }

    public function withStrongButton()
    {
        return $this->withButton('B', '(.*)', '<strong>{cursor-empty}$1</strong>');
    }

    public function withMarkButton()
    {
        return $this->withButton('Mark', '(.*)', '<mark>{cursor-empty}$1</mark>');
    }

    public function withHeadersButton()
    {
        return $this->withButton('H', null, null, [
            [
                'name' => 'H1',
                'expr' => '(.*)',
                'replace' => '<h1>{cursor-empty}$1</h1>',
            ],
            [
                'name' => 'H2',
                'expr' => '(.*)',
                'replace' => '<h2>{cursor-empty}$1</h2>',
            ],
            [
                'name' => 'H3',
                'expr' => '(.*)',
                'replace' => '<h3>{cursor-empty}$1</h3>',
            ],
        ]);
    }

    public function withListButton()
    {
        return $this->withButton('List', null, null, [
            [
                'name' => 'Unnumbered',
                'expr' => '(.*)',
                'replace' => "<ul>\n  <li>{cursor-empty}$1</li>{cursor-filled}\n</ul>",
            ],
            [
                'name' => 'Numbered',
                'expr' => '(.*)',
                'replace' => "<ol>\n  <li>{cursor-empty}$1</li>{cursor-filled}\n</ol>",
            ],
            [
                'name' => 'ul>li*3',
                'expr' => '(.*)',
                'replace' => "<ul>\n  <li>{cursor-empty}$1</li>\n  <li>{cursor-filled}</li>\n  <li></li>\n</ul>",
            ],
            [
                'name' => 'ol>li*3',
                'expr' => '(.*)',
                'replace' => "<ol>\n  <li>{cursor-empty}$1</li>\n  <li>{cursor-filled}</li>\n  <li></li>\n</ol>",
            ],
        ]);
    }

    public function withDl()
    {
        return $this->withButton('dl', '(.*)', "<dl>\n  <dt>{cursor-empty}$1</dt>\n  <dd>{cursor-filled}</dd>\n</dl>");
    }

    public function withP()
    {
        return $this->withButton('p', '(.*)', "<p>{cursor-empty}$1{cursor-filled}</p>");
    }

    public function withAbbr()
    {
        return $this->withButton('abbr', '(.*)', "<abbr title=\"{cursor-filled}\">{cursor-empty}$1</abbr>");
    }

    public function withBlockquote()
    {
        return $this->withButton('quote', '(.*)', "<blockquote>{cursor-empty}$1{cursor-empty}</blockquote>");
    }

    public function withVariablesButton(array $variables, bool $select = true)
    {
        if ($select) {
            return $this->withButton('Variables', null, null, tap(collect(), function ($collection) use ($variables) {
                foreach ($variables as $variableName => $variableValue) {
                    $collection->push([
                        'name' => $variableName,
                        'expr' => '(.*)',
                        'replace' => $variableValue,
                    ]);
                }
            })->toArray()
            );
        }

        foreach ($variables as $variableName => $variableValue) {
            $this->withButton($variableName, '(.*)', $variableValue);
        }

        return $this;
    }

    public function withButton($name, $expr, $replace, $dropdown = null)
    {
        $this->buttons[] = [
            'name' => $name,
            'expr' => $expr,
            'replace' => $replace,
            'dropdown' => $dropdown,
        ];

        return $this;
    }

    /**
     * Create a new field.
     *
     * @param  string  $name
     * @param  string|callable|null  $attribute
     * @param  callable|null  $resolveCallback
     * @return void
     */
    // public function __construct($name, $attribute = null, callable $resolveCallback = null)
    // {
    //     parent::__construct($name, $attribute, $resolveCallback);

    // }

    /**
     * Resolve the given attribute from the given resource.
     *
     * @param  mixed  $resource
     * @param  string  $attribute
     * @return mixed
     */
    protected function resolveAttribute($resource, $attribute)
    {
        $value = parent::resolveAttribute($resource, $attribute);

        if ($this->json) {
            return json_encode($value, $this->jsonOptions ?? JSON_PRETTY_PRINT);
        }

        return $value;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $requestAttribute
     * @param  object  $model
     * @param  string  $attribute
     * @return void
     */
    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        if ($request->exists($requestAttribute)) {
            $model->{$attribute} = $this->json
                ? json_decode($request[$requestAttribute], true)
                : $request[$requestAttribute];
        }
    }

    /**
     * Indicate that the code field is used to manipulate JSON.
     *
     * @param  int|null  $options
     * @return $this
     */
    public function json($options = null)
    {
        $this->json = true;

        $this->jsonOptions = $options ?? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;

        return $this->options(['mode' => 'application/json']);
    }

    /**
     * Define the language syntax highlighting mode for the field.
     *
     * @param  string  $language
     * @return $this
     */
    public function language($language)
    {
        return $this->options(['mode' => $language]);
    }

    /**
     * Set the Code editor to display all of its contents.
     *
     * @return $this
     */
    public function fullHeight()
    {
        $this->height = '100%';

        return $this;
    }

    /**
     * Set the visual height of the Code editor to automatic.
     *
     * @return $this
     */
    public function autoHeight()
    {
        $this->height = 'auto';

        return $this;
    }

    /**
     * Set the visual height of the Code editor.
     *
     * @param string|int $height
     * @return $this
     */
    public function height($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Set configuration options for the code editor instance.
     *
     * @param  array  $options
     * @return $this
     */
    public function options($options)
    {
        $currentOptions = $this->meta['options'] ?? [];

        return $this->withMeta([
            'options' => array_merge($currentOptions, $options),
        ]);
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'height' => $this->height,
            'buttons' => $this->buttons,
        ]);
    }
}
