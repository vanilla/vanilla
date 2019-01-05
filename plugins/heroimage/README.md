# Hero Image Plugin

This plugin adds a settings page to configure a default hero image, and an image upload in each category menu.

## Usage
A hero image can be fetched in 2 ways:

### PHP
```php
if (class_exists('HeroImagePlugin')) {
    $imageUrl = HeroImagePlugin::getCurrentHeroImageLink();
}
```

### Smarty
```smarty
{hero_image_link}
```

## Determining which image to show

- If there is no category for the current page (eg. profile, activity, etc.) return the default.
- If current category has a hero image and returns that.
- If a parent category has a hero image return that.
- If no parent has a hero image return the default.
