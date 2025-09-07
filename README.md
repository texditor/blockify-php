# Blockify

Blockify is a PHP library designed for processing and visualizing structured content, which is presented in the form of `texditror/editror` blocks. It provides validation, sanitization, and output of data in HTML format.

## Features

- Process JSON or array input into structured blocks
- Validate block data against configurable models
- Merge similar adjacent content items
- Render blocks to HTML with customizable output
- Development mode for error handling

## Installation

Install via Composer:

```bash
composer require texditor/blockify-php
```

## Basic Usage

```php
use Texditor\Blockify\Blockify;
use Texditor\Blockify\Config;
use Texditor\Blockify\Models\ParagraphBlock;
use Texditor\Blockify\Models\CodeBlock;
use Texditor\Blockify\Models\FilesBlock;
use Texditor\Blockify\Models\GalleryBlock;
use Texditor\Blockify\Models\HeaderBlock;
use Texditor\Blockify\Models\OrderedListBlock;
use Texditor\Blockify\Models\UnorderedListBlock;

// Initialize configuration
$config = (new Config())
    ->addModels(
        (new ParagraphBlock())
          ->setAllowedTags(['a', 'b']),
        new CodeBlock(),
        (new FilesBlock())
            ->setSourceProtocols(['https', 'http'])
            ->setSourceProtocols(['https', 'http'])
            // or only locally
            ->setSourceRegex(["/^\/uploads\/.*\.(png|jpg|jpeg|gif)$/"]),
        (new GalleryBlock())
            // ->setIsMeta(true)
            // ->setIsMetaCaption(true)
            // ->setIsMetaDesc(false)
            ->setMetaPosition('top')
            ->setImageTypes(['image/png', 'image/jpeg'])
            ->setVideoTypes(['video/mp4'])
            ->setVideoAttributes([
                'controls' => 'true'
            ]),

        new UnorderedListBlock(),
        new OrderedListBlock(),
        // Default h1
        new HeaderBlock(),
        (new HeaderBlock())
            ->setOutputName('h2')
            ->setInputName('h2'),
            //... and other block model methods (Texditor\Blockify\BlockModel)
    );
// Dev mode
$config->setDev(true);

// Process data
$blockify = (new Blockify($config))
    ->setData($jsonData);

// Get secure and processed data
$blocks = $blockify->getData();

```

### Error Handling

```php
//...
$blockify = new Blockify($config);

if (!$blockify->isValid()) {
    $errors = $blockify->getErrors();
    // Handle errors
}
```

### Rendering

```php
use Texditor\Blockify\HtmlBuilder;

//...
// Initializes the HTML builder with security and rendering settings.
$htmlBuilder = new HtmlBuilder($config);

// Renders all provided blocks into a safe HTML string.
$html = $htmlBuilder->render($blocks);

// Renders all blocks into plain text, stripping all HTML tags.
$text = $htmlBuilder->renderAsText($blocks);

// Renders only the specified block types ('p', 'h1') into HTML.
$html = $htmlBuilder->renderOnly($blocks, ["p", "h1"]);

// Renders only the specified block types into plain text.
$text = $htmlBuilder->renderOnly($blocks, ["p", "h1"], true);
```

## Available Block Models

The library includes these default block models:

- `ParagraphBlock` - For text paragraphs
- `CodeBlock` - For code snippets
- `FilesBlock` - For file attachments
- `GalleryBlock` - For image/video galleries
- `HeaderBlock` - For headings (h1-h6)
- `OrderedListBlock` - For numbered lists
- `UnorderedListBlock` - For bulleted lists

## Configuration Options

- `addModels()` - Register block models
- `setDev()` - Enable/disable development mode
- `setBlockCssPrefix()` - Set CSS class prefix
- `setRenderTagNames()` - Customize HTML tag names
- `setRenderBlockNames()` - Customize block output tags
