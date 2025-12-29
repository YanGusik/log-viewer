<div align="center">
    <p>
        <h1>Log Viewer<br/>Easy-to-use, fast, and beautiful</h1>
    </p>
</div>

<p align="center">
    <a href="https://log-viewer.opcodes.io/">Documentation</a> |
    <a href="#features">Features</a> |
    <a href="#custom-fork-features">Custom Fork Features</a> |
    <a href="#installation">Installation</a> |
    <a href="#troubleshooting">Troubleshooting</a> |
    <a href="#credits">Credits</a>
</p>

<p align="center">
<a href="https://packagist.org/packages/opcodesio/log-viewer"><img src="https://img.shields.io/packagist/v/opcodesio/log-viewer.svg?style=flat-square" alt="Packagist"></a>
<a href="https://packagist.org/packages/opcodesio/log-viewer"><img src="https://img.shields.io/packagist/dm/opcodesio/log-viewer.svg?style=flat-square" alt="Packagist"></a>
<a href="https://packagist.org/packages/opcodesio/log-viewer"><img src="https://img.shields.io/packagist/php-v/opcodesio/log-viewer.svg?style=flat-square" alt="PHP from Packagist"></a>
<a href="https://packagist.org/packages/opcodesio/log-viewer"><img src="https://img.shields.io/badge/Laravel-8.x,%209.x,%2010.x,%2011.x,%2012.x-brightgreen.svg?style=flat-square" alt="Laravel Version"></a>
</p>

> **Note**: This is a custom fork of [opcodesio/log-viewer](https://github.com/opcodesio/log-viewer) with enhanced exception debugging features.
>
> 👤 **Fork maintained by**: [Yan Gusik](https://github.com/YanGusik)

![log-viewer-light-dark](https://user-images.githubusercontent.com/8697942/186705175-d51db6ef-1615-4f94-aa1e-3ecbcb29ea24.png)


[OPcodes's](https://www.opcodes.io/) **Log Viewer** is a perfect companion for your [Laravel](https://laravel.com/) app.

You will no longer need to read the raw Laravel log files (and other types of logs) trying to find what you're looking for.

Log Viewer helps you quickly and clearly see individual log entries, to **search**, **filter**, and make sense of your Laravel logs **fast**. It is free and easy to install.

> 📺 **[Watch a quick 4-minute video](https://www.youtube.com/watch?v=q7SnF2vubRE)** showcasing some Log Viewer features.

### Features

- 📂 **View all the Laravel logs** in your `storage/logs` directory,
- 📂 **View other types of logs** - Horizon, Apache, Nginx, Redis, Supervisor, Postgres, and more,
- 🔍 **Search** the logs,
- 🎚 **Filter** by log level (error, info, debug, etc.),
- 🔗 **Sharable links** to individual log entries,
- 🌑 **Dark mode**,
- 📱 **Mobile-friendly** UI,
- 🖥️ **Multiple host support**,
- ⌨️ **Keyboard accessible**,
- 💾 **Download & delete** log files from the UI,
- ☑️ **Horizon** log support (up to Horizon v9.20),
- ☎️ **API access** for folders, files & log entries,
- 💌 **Mail previews** for e-mails sent to the logs,
- and more...

## Custom Fork Features

This fork adds **Ignition-style exception debugging** to enhance error analysis:

![Exception Debugging Screenshot](screenshot.jpg)

### 🔥 Enhanced Exception Debugging

- **📝 Code Snippets** - View the exact line where the exception occurred with surrounding code context
- **🎨 Syntax Highlighting** - PHP syntax highlighting using highlight.js for better code readability
- **📚 Detailed Stack Trace** - Interactive stacktrace with code snippets for each frame
- **🔗 Nested Exceptions** - Full support for previous/nested exceptions with complete details
- **📦 Vendor/App Separation** - Clearly distinguish between your application code and vendor packages
- **⚙️ Configurable** - Customize snippet line count and vendor code display
- **🌙 Dark Mode** - Beautiful dark theme support for all debugging features

For detailed documentation about custom features, see [CUSTOM_FEATURES.md](CUSTOM_FEATURES.md).

### Documentation

Documentation can be found on the [official website](https://log-viewer.opcodes.io/).

## Get Started

### Requirements

- **PHP 8.0+**
- **Laravel 8+**

### Installation

#### Installing This Fork

To install this fork with enhanced exception debugging features, add the following to your `composer.json`:

```json
{
  "repositories": {
    "log-viewer": {
      "type": "vcs",
      "url": "https://github.com/YanGusik/log-viewer"
    }
  },
  "require": {
    "opcodesio/log-viewer": "dev-feature/ignition-style-exception-debugging"
  }
}
```

Then run:

```bash
composer update opcodesio/log-viewer
```

After installing the package, publish the front-end assets by running:

```bash
php artisan log-viewer:publish
```

#### Installing Original Package

To install the original package via composer, Run:

```bash
composer require opcodesio/log-viewer
```

After installing the package, publish the front-end assets by running:

```bash
php artisan log-viewer:publish
```

### Usage

Once the installation is complete, you will be able to access **Log Viewer** directly in your browser.

By default, the application is available at: `{APP_URL}/log-viewer`.

(for example: `https://my-app.test/log-viewer`)

## Configuration

Please visit the **[Log Viewer Docs](https://log-viewer.opcodes.io/docs)** to learn about configuring Log Viewer to your needs.

## Troubleshooting

Here are some common problems and solutions.

### Problem: Logs not loading

Please see [this page](https://log-viewer.opcodes.io/docs/3.x/log-types/default) for support log formats. If your log has a custom format, or is not supported by Log Viewer out of the box, you will need to [define your own custom log parser](https://log-viewer.opcodes.io/docs/3.x/log-types/custom).

If your logs are still not showing up, make sure the web process, which Log Viewer runs on, has permission to read these logs.

For example, if you want to read the Apache HTTP access logs in `/var/log/httpd`, you will need to make sure that your web process (apache/httpd) has permission to read these files. On unix systems, you can do this with [file ACLs](https://www.thegeekdiary.com/unix-linux-access-control-lists-acls-basics/#:~:text=Every%20file%20on%20any%20UNIX,their%20permission%20to%20the%20file).

## Screenshots

Read the **[release blog post](https://arunas.dev/log-viewer-for-laravel/)** for screenshots and more information about Log Viewer's features.

The **[release of v2](https://arunas.dev/log-viewer-v2/)** includes a few new features in v2.

The **[release of v3](https://arunas.dev/log-viewer-v3/)** includes a few new features in v3.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

### Original Package

- [Arunas Skirius](https://github.com/arukompas)
- [All Contributors](../../contributors)

### Fork Author

- **[Yan Gusik](https://github.com/YanGusik)** - Exception debugging enhancements

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
