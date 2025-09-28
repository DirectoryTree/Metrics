![Phiki](./art/banner.png)

Phiki is a syntax highlighter written in PHP. It uses TextMate grammar files and Visual Studio Code themes to generate syntax highlighted code for the web.

The name and public API of Phiki is heavily inspired by [Shiki](https://shiki.style/), a package that does more or less the same thing in the JavaScript ecosystem. The actual implementation of the package is also heavily inspired by [`vscode-textmate`](https://github.com/microsoft/vscode-textmate) which is the powerhouse of a package behind Visual Studio Code, Shiki, and others.

## Installation

Install Phiki via Composer:

```sh
composer require phiki/phiki
```

## Documentation

For more information on how to integrate Phiki into your application, please [visit the official documentation](https://phiki.dev).

## Support my work

If you find Phiki useful, please consider supporting me through [GitHub Sponsors](https://github.com/sponsors/ryangjchandler) or [Buy me a Coffee](https://buymeacoffee.com/ryangjchandler).

All sponsorships go towards the maintenance and continuous improvement of my open source projects.

## Credits

* [Ryan Chandler](https://github.com/ryangjchandler)
* [Shiki](https://shiki.style/) for API inspiration and TextMate grammar files via [`tm-grammars` and `tm-themes`](https://github.com/shikijs/textmate-grammars-themes).
* [`vscode-textmate`](https://github.com/microsoft/vscode-textmate) for guiding the implementation of the internal tokenizer.
