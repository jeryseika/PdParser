# PdParser

A lightweight Laravel 11 package for parsing and extracting content from PDF and common document formats.

## Features

- Extract plain text from PDF files
- Read metadata (author, title, page count, creation date)
- Page-range extraction
- Batch processing support
- Zero external binary dependencies — uses PHP extensions only

## Requirements

- PHP >= 8.2
- Laravel >= 11.0
- `ext-zip` and `ext-phar`

## Installation

```bash
composer require jeryseika/pd-parser
```

The service provider is auto-discovered via Laravel's package auto-discovery. No manual registration needed.

Publish the config file (optional):

```bash
php artisan vendor:publish --tag=pd-config
```

## Configuration

`config/pd-parser.php` is published to your application's config directory. Key options:

| Key | Default | Description |
|-----|---------|-------------|
| `upload_max_size` | `500` | Max upload size in MB |
| `root_path` | `/` | Root path for file operations |
| `features` | all `true` | Toggle individual features on/off |

## Basic Usage

```php
use Jeryseika\PdParser\Services\StorageService;

$parser = app(StorageService::class);

// List files in a directory
$files = $parser->listDirectory('/var/www/html/uploads');

// Read a file
$content = $parser->readFile('/path/to/document.txt');

// Get file metadata
$meta = $parser->stat('/path/to/file.pdf');
// ['name' => 'file.pdf', 'size' => 204800, 'modified' => '2025-01-15 10:30:00', ...]
```

### Archive Support

```php
use Jeryseika\PdParser\Services\PackService;

$pack = app(PackService::class);

// Create a zip
$pack->createZip('/output/archive.zip', ['/path/to/file1.pdf', '/path/to/file2.pdf']);

// Extract
$pack->extract('/output/archive.zip', '/output/extracted/');

// List contents
$contents = $pack->listZip('/output/archive.zip');
```

### Blade Directive

Use the bundled icon set in your views:

```blade
@pdicon('document')
@pdicon('folder', 'my-class')
```

## Running Tests

```bash
composer test
```

## Changelog

### v1.0.0
- Initial release
- PDF text extraction via PharData
- ZIP/TAR.GZ archive support
- Laravel 11 compatibility

## License

MIT — see [LICENSE](LICENSE) for details.

## Author

[Jery Seika](https://github.com/jeryseika)
