# ![LaMula logo](./media/lamula-logo.svg) LaMula - Laravel MultiLanguage
 LaMula provides an easy way to integrate the DeepL API and localize your Laravel projects into any language.

This project is licensed under the terms of the MIT license.

The core idea behind this project is to leverage the `resources/lang` structure to automatically translate your source language into other available languages using various translation providers*.

If your project already has an English language file, generating new translations is as simple as running a command.

*Currently, DeepL is the only supported provider, but additional providers will be added in the future.

## Prerequisites
For this version, you need a valid DeepL account and API key.

DeepL offers a free API key with a usage limit of up to 500,000 characters of translation in any supported language. For more details, visit their website at DeepL API Plans (https://www.deepl.com/en/pro/change-plan#developer).

## Installation
You can integrate LaMula easily using Composer. Add this package to the `require` section of your `composer.json` file:

```
    "require": {
        "blare/lamula": "dev-main"
    }
```
Then, run `composer update`.

That's it. To confirm everything is set up correctly, run `php artisan` in the root directory of your Laravel project. You should see a new command called `lamula:deepl`.

## Usage
To use this package, you will need a valid DeepL API key as mentioned earlier.

Add your API key to your `.env` file. You can refer to `.env.example` for guidance:

```
LAMULA_DEEPL_API_KEY=your-api-key-here
LAMULA_RESOURCE_DIR=resources/lang/
```

Usually the resource directory is in `resources/lang` folder, but feel free to change if necessary.

## Example

Once your `.env` file is configured, you can run the following command to display a list of available languages:

```
php artisan lamula:deepl --show-langs
```

To translate, specify the source and target languages using a command like this:
```
php artisan lamula:deepl --source-lang=en --target-lang=fr
```

The previous command will:
1. Locate the `en` directory under `resources/lang`.
2. Scan all translation files in this directory.
3. Translate the content using the DeepL API.
4. Create a new `fr` directory under `resources/lang`.
5. Recreate the source file structure with translations in the target language.

If the destination file already exists, it will be renamed to preserve its previous content in case you need to restore or review it.

## Demo
![Project first demo](./media/output.gif)

## TODO
 - Exclude placeholders (e.g. `:amount`) from automatic translation.
  - Implement recursive translations for nested array structures like `public.legal.title`.
  - Add support for DeepL’s PRO (paid) API endpoint.
  - Incorporate additional translation providers, such as Google Cloud Translation API, Microsoft Translator Text API, Amazon Translate, Yandex.Translate API, LibreTranslate are some potential candidates.

## Disclaimer
This project is not affiliated with, sponsored, or endorsed by any company in any way. All trademarks, trade names, and logos related are property of their respective owners. This software is developed independently and aims to provide general-purpose tools without any direct association.

## Contributions
Contributions are welcome! Feel free to submit your changes via a pull request.

