# Theme System Documentation

## Overview
This document provides information about the theme system used in the application. The theme system uses CSS variables for easy customization of colors and other visual elements.

## CSS Variables
The theme system is based on CSS variables (custom properties) defined in `public/css/theme.css`. These variables are used throughout the application's stylesheets to ensure consistent styling and easy theme customization.

### Variable Categories

#### Primary Colors
```css
--color-primary: #050707;
--color-secondary: #444;
```
These variables define the primary and secondary colors used throughout the application.

#### Text Colors
```css
--color-h1: #FDE273;
--color-h2: #C4C4C4;
--color-text: #FFFFFF;
```
These variables define the colors for different text elements:
- `--color-h1`: Color for h1 headings
- `--color-h2`: Color for h2 headings
- `--color-text`: Color for regular text

#### Background Colors
```css
--color-bg-main: #444;
--color-bg-header: #050707;
--color-bg-footer: #050707;
```
These variables define the background colors for different sections of the application:
- `--color-bg-main`: Background color for the main content area
- `--color-bg-header`: Background color for the header
- `--color-bg-footer`: Background color for the footer

#### UI Element Colors
```css
--color-link: #fde273;
--color-link-hover: #ffcc00;
--color-button: #555;
--color-button-hover: #666;
```
These variables define the colors for various UI elements:
- `--color-link`: Color for links
- `--color-link-hover`: Color for links when hovered
- `--color-button`: Background color for buttons
- `--color-button-hover`: Background color for buttons when hovered

## Using the Theme Variables
To use the theme variables in your CSS, reference them with the `var()` function:

```css
.my-element {
    color: var(--color-text);
    background-color: var(--color-bg-main);
}

.my-heading {
    color: var(--color-h1);
}

.my-link {
    color: var(--color-link);
}

.my-link:hover {
    color: var(--color-link-hover);
}

.my-button {
    background-color: var(--color-button);
}

.my-button:hover {
    background-color: var(--color-button-hover);
}
```

## Customizing the Theme
To customize the theme, simply modify the CSS variable values in `public/css/theme.css`. This will automatically update all elements that use these variables throughout the application.

### Example: Creating a Light Theme
```css
:root {
  /* Primary colors */
  --color-primary: #ffffff;
  --color-secondary: #f0f0f0;

  /* Text colors */
  --color-h1: #333333;
  --color-h2: #555555;
  --color-text: #000000;

  /* Background colors */
  --color-bg-main: #ffffff;
  --color-bg-header: #f0f0f0;
  --color-bg-footer: #f0f0f0;

  /* Other UI elements */
  --color-link: #0066cc;
  --color-link-hover: #004499;
  --color-button: #0066cc;
  --color-button-hover: #004499;
}
```

## Additional Styling Files
In addition to the theme variables, the application includes several other CSS files for specific components:

- `public/css/auth.css`: Styles for authentication pages
- `public/css/content.css`: Styles for main content areas
- `public/css/docs.css`: Styles for documentation pages
- `public/css/footer.css`: Styles for the footer
- `public/css/header.css`: Styles for the header
- `public/css/starry-background.css`: Styles for the starry background effect
- `public/css/admin/users/api-keys.css`: Styles for API keys management pages
- `public/css/admin/users/api-keys.css`: Styles for API keys management pages

## Best Practices
1. **Always use theme variables**: Instead of hardcoding colors, always use the theme variables to ensure consistency and make future theme changes easier.
2. **Maintain contrast**: When customizing the theme, ensure that text remains readable by maintaining sufficient contrast between text and background colors.
3. **Test changes**: After making theme changes, test the application on different devices and browsers to ensure compatibility.
4. **Document custom themes**: If you create custom themes, document the color schemes for future reference.

## Related Documentation
- [Views Documentation](../Views.md) - Documentation for Blade templates and view structure
