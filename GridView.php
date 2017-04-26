<?php

namespace ydakilux\footable;

use Yii;
use Closure;
use yii\i18n\Formatter;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Html;
use yii\helpers\Url;
//use yii\grid\Column;
use yii\widgets\BaseListView;

class GridView extends \yii\grid\GridView
//class GridView extends BaseListView
{
    public $footableOptions = [];
    /**
     * @var array|Formatter the formatter used to format model attribute values into displayable texts.
     * This can be either an instance of [[Formatter]] or an configuration array for creating the [[Formatter]]
     * instance. If this property is not set, the "formatter" application component will be used.
     */
    public $formatter;
    /**
     * @var array grid column configuration. Each array element represents the configuration
     * for one particular grid column. For example,
     *
     * ```php
     * [
     *     ['class' => SerialColumn::className()],
     *     [
     *         'class' => DataColumn::className(), // this line is optional
     *         'attribute' => 'name',
     *         'format' => 'text',
     *         'label' => 'Name',
     *     ],
     *     ['class' => CheckboxColumn::className()],
     * ]
     * ```
     *
     * If a column is of class [[DataColumn]], the "class" element can be omitted.
     *
     * As a shortcut format, a string may be used to specify the configuration of a data column
     * which only contains [[DataColumn::attribute|attribute]], [[DataColumn::format|format]],
     * and/or [[DataColumn::label|label]] options: `"attribute:format:label"`.
     * For example, the above "name" column can also be specified as: `"name:text:Name"`.
     * Both "format" and "label" are optional. They will take default values if absent.
     *
     * Using the shortcut format the configuration for columns in simple cases would look like this:
     *
     * ```php
     * [
     *     'id',
     *     'amount:currency:Total Amount',
     *     'created_at:datetime',
     * ]
     * ```
     *
     * When using a [[dataProvider]] with active records, you can also display values from related records,
     * e.g. the `name` attribute of the `author` relation:
     *
     * ```php
     * // shortcut syntax
     * 'author.name',
     * // full syntax
     * [
     *     'attribute' => 'author.name',
     *     // ...
     * ]
     * ```
     */
    public $columns = [];
    /**
     * @var string the layout that determines how different sections of the list view should be organized.
     * The following tokens will be replaced with the corresponding section contents:
     *
     * - `{summary}`: the summary section. See [[renderSummary()]].
     * - `{errors}`: the filter model error summary. See [[renderErrors()]].
     * - `{items}`: the list items. See [[renderItems()]].
     * - `{sorter}`: the sorter. See [[renderSorter()]].
     * - `{pager}`: the pager. See [[renderPager()]].
     */
    public $layout = "{summary}\n{items}\n{pager}";
    /**
     * @var boolean, if true use internal filter of footable
     */
    public $filter = false;
    public $sorter = false;
    public $breakpoint = false;
    /**
     * Set download target for grid export to a popup browser window
     */
    const TARGET_POPUP = '_popup';
    /**
     * Set download target for grid export to the same open document on the browser
     */
    const TARGET_SELF = '_self';
    /**
     * Set download target for grid export to a new window that auto closes after download
     */
    const TARGET_BLANK = '_blank';
    /**
     * @var array|string the toolbar content configuration. Can be setup as a string or an array. When set as a
     * _string_, it will be rendered as is. When set as an _array_, each line item will be considered as per the
     * following rules:
     * - if the line item is setup as a _string_, it will be rendered as is
     * - if the line item is an _array_, the following keys can be setup to control the rendering of the toolbar:
     *     - `content`: _string_, the content to be rendered as a bootstrap button group. The following special tags
     *       in the content are recognized and will be replaced:
     *         - `{export}`, _string_ which will render the [[export]] menu button content.
     *         - `{toggleData}`, _string_ which will render the button to toggle between page data and all data.
     *         - `options`: _array_, the HTML attributes for the button group div container. By default the CSS class
     *           `btn-group` will be attached to this container if no class is set.
     */
    public $toolbar = [
        '{toggleData}',
        '{export}',
    ];
    /**
     * @var string the default pagination that will be read by toggle data. Should be one of 'page' or 'all'. If not
     * set to 'all', it will always defaults to 'page'.
     */
    public $defaultPagination = 'page';
    /**
     * @var boolean whether to enable toggling of grid data. Defaults to `true`.
     */
    public $toggleData = true;
    /**
     * @var array the settings for the toggle data button for the toggle data type. This will be setup as an
     * associative array of $key => $value pairs, where $key can be:
     * - `maxCount`: `int`|`boolean`, the maximum number of records uptil which the toggle button will be rendered. If
     *   the dataProvider records exceed this setting, the toggleButton will not be displayed. Defaults to `10000` if
     *   not set. If you set this to `true`, the toggle button will always be displayed. If you set this to `false
     *   the toggle button will not be displayed (similar to `toggleData` setting).
     * - `minCount`: `int`|`boolean`, the minimum number of records beyond which a confirmation message will be
     *   displayed when toggling all records. If the dataProvider record count exceeds this setting, a confirmation
     *   message will be alerted to the user. Defaults to `500` if not set. If you set this to `true`, the
     *   confirmation message will always be displayed. If set to `false` no confirmation message will be displayed.
     * - `confirmMsg`: _string_, the confirmation message for the toggle data when `minCount` threshold is exceeded.
     *   Defaults to `'There are {totalCount} records. Are you sure you want to display them all?'`.
     * - `all`: _array_, configuration for showing all grid data and the value is the HTML attributes for the button.
     *   (refer `page` for understanding the default options).
     * - `page`: _array_, configuration for showing first page data and $options is the HTML attributes for the button.
     *    The following special options are recognized:
     *    - `icon`: _string_, the glyphicon suffix name. If not set or empty will not be displayed.
     *    - `label`: _string_, the label for the button.
     *
     *      This defaults to the following setting:
     *
     *      ```php
     *      [
     *          'maxCount' => 10000,
     *          'minCount' => 1000
     *          'confirmMsg' => Yii::t(
     *              'kvgrid',
     *              'There are {totalCount} records. Are you sure you want to display them all?',
     *              ['totalCount' => number_format($this->dataProvider->getTotalCount())]
     *          ),
     *          'all' => [
     *              'icon' => 'resize-full',
     *              'label' => 'All',
     *              'class' => 'btn btn-default',
     *              'title' => 'Show all data'
     *          ],
     *          'page' => [
     *              'icon' => 'resize-small',
     *              'label' => 'Page',
     *              'class' => 'btn btn-default',
     *              'title' => 'Show first page data'
     *          ],
     *      ]
     *      ```
     */
    public $toggleDataOptions = [];
    /**
     * @var array the HTML attributes for the toggle data button group container. By default this will always have the
     * `class = btn-group` automatically added, if no class is set.
     */
    public $toggleDataContainer = [];
    /**
     * @var boolean whether the grid view will be rendered within a pjax container. Defaults to `false`. If set to
     * `true`, the entire GridView widget will be parsed via Pjax and auto-rendered inside a yii\widgets\Pjax
     * widget container. If set to `false` pjax will be disabled and none of the pjax settings will be applied.
     */
    public $pjax = false;
    /**
     * @var boolean whether the current mode is showing all data
     */
    protected $_isShowAll = false;
    /**
     * @var string key to identify showing all data
     */
    protected $_toggleDataKey;
    /**
     * @var string HTML attribute identifier for the toggle button
     */
    protected $_toggleButtonId;
    /* --------------------------------------------------------------------------------------------------------------------- */
    /* --------------------------------------------------------------------------------------------------------------------- */
    /* --------------------------------------------------------------------------------------------------------------------- */
    /* --------------------------------------------------------------------------------------------------------------------- */

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (empty($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
        if (!$this->toggleData) {
            parent::init();
            return;
        }
        $this->_toggleDataKey = '_tog' . hash('crc32', $this->options['id']);
        $this->_isShowAll = ArrayHelper::getValue($_GET, $this->_toggleDataKey, $this->defaultPagination) === 'all';
        if ($this->_isShowAll) {
            /** @noinspection PhpUndefinedFieldInspection */
            $this->dataProvider->pagination = false;
        }
        $this->_toggleButtonId = $this->options['id'] . '-togdata-' . ($this->_isShowAll ? 'all' : 'page');
        parent::init();
    }

    /**
     * Sets a default css class within `options` if not set
     *
     * @param array $options the HTML options
     * @param string $css the CSS class to test and append
     */
    protected static function initCss(&$options, $css)
    {
        if (!isset($options['class'])) {
            $options['class'] = $css;
        }
    }

    /**
     * Initialize toggle data button options.
     */
    protected function initToggleData()
    {
        if (!$this->toggleData) {
            return;
        }
        $defaultOptions = [
            'maxCount' => 10000,
            'minCount' => 500,
            'confirmMsg' => Yii::t(
                'app',
                'There are {totalCount} records. Are you sure you want to display them all?',
                ['totalCount' => number_format($this->dataProvider->getTotalCount())]
            ),
            'all' => [
                'icon' => 'resize-full',
                'label' => Yii::t('app', 'All'),
                'class' => 'btn btn-default',
                'title' => Yii::t('app', 'Show all data'),
            ],
            'page' => [
                'icon' => 'resize-small',
                'label' => Yii::t('app', 'Page'),
                'class' => 'btn btn-default',
                'title' => Yii::t('app', 'Show first page data'),
            ],
        ];
        $this->toggleDataOptions = array_replace_recursive($defaultOptions, $this->toggleDataOptions);
        $tag = $this->_isShowAll ? 'page' : 'all';
        $options = $this->toggleDataOptions[$tag];
        $this->toggleDataOptions[$tag]['id'] = $this->_toggleButtonId;
        $icon = ArrayHelper::remove($this->toggleDataOptions[$tag], 'icon', '');
        $label = !isset($options['label']) ? $defaultOptions[$tag]['label'] : $options['label'];
        if (!empty($icon)) {
            $label = "<i class='glyphicon glyphicon-{$icon}'></i> " . $label;
        }
        $this->toggleDataOptions[$tag]['label'] = $label;
        if (!isset($this->toggleDataOptions[$tag]['title'])) {
            $this->toggleDataOptions[$tag]['title'] = $defaultOptions[$tag]['title'];
        }
        $this->toggleDataOptions[$tag]['data-pjax'] = $this->pjax ? "true" : false;
    }

    /**
     * Renders the toggle data button.
     *
     * @return string
     */
    public function renderToggleData()
    {
        if (!$this->toggleData) {
            return '';
        }
        $maxCount = ArrayHelper::getValue($this->toggleDataOptions, 'maxCount', false);
        if ($maxCount !== true && (!$maxCount || (int)$maxCount <= $this->dataProvider->getTotalCount())) {
            return '';
        }
        $tag = $this->_isShowAll ? 'page' : 'all';
        $options = $this->toggleDataOptions[$tag];
        $label = ArrayHelper::remove($options, 'label', '');
        $url = Url::current([$this->_toggleDataKey => $tag]);
        static::initCss($this->toggleDataContainer, 'btn-group  pull-right');
        return Html::tag('div', Html::a($label, $url, $options), $this->toggleDataContainer);
    }

    /**
     * Renders the table header.
     * @return string the rendering result.
     */
    public function renderTableHeader()
    {
        $cells = [];
        $i = 0;
        foreach ($this->columns as $column) {
            /* @var $column Column */
            /* If filter disable, standard columns header with aref */
            if (!$this->filter) {
                $cells[] = $column->renderHeaderCell();
            } else {
                $columnsoptions = null;
                if ($this->breakpoint = true) {
                    switch ($i) {
                        case 0 :
                            $columnsoptions = ['data-breakpoints' => 'xs'];
                            break;
                        case 1 :
                            $columnsoptions = ['data-breakpoints' => 'xs'];
                            break;
                        case 2 :
                        case 3 :
                            $columnsoptions = ['data-breakpoints' => 'xs sm'];
                            break;
                        case 4 :
                        case 5 :
                        case 6 :
                        case 7 :
                            $columnsoptions = ['data-breakpoints' => 'xs sm md'];
                            break;
                        case 8 :
                        case 9 :
                        case 10 :
                        case 11 :
                            $columnsoptions = ['data-breakpoints' => 'xs sm lg'];
                            break;
                        default :
                            $columnsoptions = ['data-breakpoints' => 'all'];
                    }
                }
                if (!isset($column->header)) {
                    $cells[] = Html::tag('th', $column->attribute, $columnsoptions);
                } else {
                    $cells[] = Html::tag('th', $column->header, $columnsoptions);
                }
            }
            $i++;
        }
        $content = Html::tag('tr', implode('', $cells), $this->headerRowOptions);
        return "<thead>\n" . $content . "\n</thead>";
    }

    /**
     * Renders the data models for the grid view.
     */
    public function renderItems()
    {
        $caption = $this->renderCaption();
        $columnGroup = $this->renderColumnGroup();
        $tableHeader = $this->showHeader ? $this->renderTableHeader() : false;
        $tableBody = $this->renderTableBody();
        $tableFooter = $this->showFooter ? $this->renderTableFooter() : false;
//        Yii::warning('this caption  : ' . Json::encode($caption));
//        Yii::warning('this columnGroup  : ' . Json::encode($columnGroup));
//        Yii::warning('this tableHeader  : ' . Json::encode($tableHeader));
//        Yii::warning('this tableBody  : ' . Json::encode($tableBody));
//        Yii::warning('this tableFooter  : ' . Json::encode($tableFooter));
        $content = array_filter([
            $caption,
            $columnGroup,
            $tableHeader,
            $tableFooter,
            $tableBody,
        ]);
//        Yii::warning('table options  : ' . Json::encode($this->tableOptions));
//        Yii::warning('footable options : ' . Json::encode($this->footableOptions));
        if ($this->filter == true) {
            $this->tableOptions = array_merge($this->tableOptions, ['data-filtering' => 'true']);
        }
        if ($this->sorter == true) {
            $this->tableOptions = array_merge($this->tableOptions, ['data-sorting' => 'true']);
        }
        if ($this->breakpoint == true) {
            $this->tableOptions = array_merge($this->tableOptions, ['data-cascade' => 'true']);
        }
        // Enable - disable columns
        $this->tableOptions = array_merge($this->tableOptions, ['data-show-toggle' => 'true']);
        // Always expand first row of data
        $this->tableOptions = array_merge($this->tableOptions, ['data-expand-first' => 'true']);
        if (!$this->_isShowAll) {
            $this->tableOptions = array_merge($this->tableOptions, ['data-paging' => "true", 'data-paging-size' => 20, 'data-paging-widget' => "true", 'data-paging-widget-count-format' => "{CP} of {TP}"]);
        }
        return Html::tag('table', implode("\n", $content), $this->tableOptions);
    }

    /**
     * @inheritdoc
     */
    public function renderSection($name)
    {
        switch ($name) {
            case '{errors}':
                return $this->renderErrors();
            case '{toolbar}':
                return 'this is a test';
            default:
                return parent::renderSection($name);
        }
    }

    public function run()
    {
        FootableAsset::register($this->getView());
        $this->registerScript();
//        parent::run();
        $this->initToggleData();
        $headertoggleData = $this->renderToggleData();
        echo '<div class="well">';
        echo $headertoggleData;
        echo '</div';
        if ($this->showOnEmpty || $this->dataProvider->getCount() > 0) {
            $content = preg_replace_callback("/{\\w+}/", function ($matches) {
                $content = $this->renderSection($matches[0]);
                return $content === false ? $matches[0] : $content;
            }, $this->layout);
        } else {
            $content = $this->renderEmpty();
        }
        $options = $this->options;
//        Yii::warning('Options : ' . Json::encode($options));
        $tag = ArrayHelper::remove($options, 'tag', 'div');
//        Yii::warning('Tags : ' . Json::encode($tag));
//        Yii::warning('Options after tag : ' . Json::encode($tag));
        return Html::tag($tag, $content, $options);
//        return $this->renderItems();
    }

    protected function registerScript()
    {
        $configure = !empty($this->footableOptions) ? Json::encode($this->footableOptions) : '';
        $this->getView()->registerJs("jQuery('#{$this->options['id']}').footable({$configure});");
        $configure = !empty($this->footableOptions['options']) ? Json::encode($this->footableOptions['options']) : '';
//        Yii::warning('ID : ' . Json::encode($this->options['id']));
//        Yii::warning('Grid : ' . Json::encode($this->getClientOptions()));
//        Yii::warning('Options : ' . Json::encode($this->options));
//
    }
}
