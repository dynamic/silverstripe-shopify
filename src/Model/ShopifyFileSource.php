<?php

namespace Dynamic\Shopify\Model;

use SilverStripe\ORM\DataObject;

/**
 * Class ShopifyFileSource
 * @package Dynamic\Shopify\Model
 *
 * @property string Format
 * @property string MimeType
 * @property string URL
 * @property int Width
 * @property int Height
 *
 * @property int FileID
 * @method ShopifyFile File
 */
class ShopifyFileSource extends DataObject
{
    /**
     * @config
     * @var string
     */
    private static $table_name = "ShopifyFileSource";

    /**
     * @config
     * @var string[]
     */
    private static $db = [
        'Format' => 'Varchar(255)',
        'MimeType' => 'Varchar(255)',
        'URL' => 'Varchar(255)',
        'Width' => 'Int',
        'Height' => 'Int',
    ];

    /**
     * @config
     * @var string[]
     */
    private static $has_one = [
        'File' => ShopifyFile::class,
    ];

    /**
     * @var string[]
     */
    private static $summary_fields = [
        'ID',
        'Format',
        'MimeType',
        'Width',
        'Height',
    ];
}
