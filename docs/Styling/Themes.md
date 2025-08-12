# Theme System Documentation

## Overview
This document provides information about the theme system used in the application. The theme system uses CSS variables for easy customization of colors and other visual elements.

## CSS Variables
The theme system is based on CSS variables (custom properties) defined in `public/css/theme.css`. These variables are used throughout the application's stylesheets to ensure consistent styling and easy theme customization.

### Variable Categories

#### Primary Colors
| Variable | Hex Code | Color Preview | Color Description |
|----------|----------|--------------|-------------------|
| `--color-primary` | `#050707` | <span style="display:inline-block; width:20px; height:20px; background-color:#050707; border:1px solid #ccc;"></span> | Near black |
| `--color-secondary` | `#444` | <span style="display:inline-block; width:20px; height:20px; background-color:#444; border:1px solid #ccc;"></span> | Dark gray |

These variables define the primary and secondary colors used throughout the application.

#### Text Colors
| Variable | Hex Code | Color Preview | Color Description |
|----------|----------|--------------|-------------------|
| `--color-h1` | `#FDE273` | <span style="display:inline-block; width:20px; height:20px; background-color:#FDE273; border:1px solid #ccc;"></span> | Bright yellow |
| `--color-h2` | `#C4C4C4` | <span style="display:inline-block; width:20px; height:20px; background-color:#C4C4C4; border:1px solid #ccc;"></span> | Light gray |
| `--color-text` | `#FFFFFF` | <span style="display:inline-block; width:20px; height:20px; background-color:#FFFFFF; border:1px solid #ccc;"></span> | White |

These variables define the colors for different text elements:
- `--color-h1`: Color for h1 headings
- `--color-h2`: Color for h2 headings
- `--color-text`: Color for regular text

#### Background Colors
| Variable | Hex Code | Color Preview | Color Description |
|----------|----------|--------------|-------------------|
| `--color-bg-main` | `#444` | <span style="display:inline-block; width:20px; height:20px; background-color:#444; border:1px solid #ccc;"></span> | Dark gray |
| `--color-bg-header` | `#050707` | <span style="display:inline-block; width:20px; height:20px; background-color:#050707; border:1px solid #ccc;"></span> | Near black |
| `--color-bg-footer` | `#050707` | <span style="display:inline-block; width:20px; height:20px; background-color:#050707; border:1px solid #ccc;"></span> | Near black |

These variables define the background colors for different sections of the application:
- `--color-bg-main`: Background color for the main content area
- `--color-bg-header`: Background color for the header
- `--color-bg-footer`: Background color for the footer

#### UI Element Colors
| Variable | Hex Code | Color Preview | Color Description |
|----------|----------|--------------|-------------------|
| `--color-link` | `#fde273` | <span style="display:inline-block; width:20px; height:20px; background-color:#fde273; border:1px solid #ccc;"></span> | Bright yellow |
| `--color-link-hover` | `#ffcc00` | <span style="display:inline-block; width:20px; height:20px; background-color:#ffcc00; border:1px solid #ccc;"></span> | Golden yellow |
| `--color-button` | `#555` | <span style="display:inline-block; width:20px; height:20px; background-color:#555; border:1px solid #ccc;"></span> | Medium gray |
| `--color-button-hover` | `#666` | <span style="display:inline-block; width:20px; height:20px; background-color:#666; border:1px solid #ccc;"></span> | Darker gray |

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

#### Light Theme Colors
| Variable | Hex Code | Color Preview | Color Description |
|----------|----------|--------------|-------------------|
| `--color-primary` | `#ffffff` | <span style="display:inline-block; width:20px; height:20px; background-color:#ffffff; border:1px solid #ccc;"></span> | White |
| `--color-secondary` | `#f0f0f0` | <span style="display:inline-block; width:20px; height:20px; background-color:#f0f0f0; border:1px solid #ccc;"></span> | Very light gray |
| `--color-h1` | `#333333` | <span style="display:inline-block; width:20px; height:20px; background-color:#333333; border:1px solid #ccc;"></span> | Dark gray |
| `--color-h2` | `#555555` | <span style="display:inline-block; width:20px; height:20px; background-color:#555555; border:1px solid #ccc;"></span> | Medium gray |
| `--color-text` | `#000000` | <span style="display:inline-block; width:20px; height:20px; background-color:#000000; border:1px solid #ccc;"></span> | Black |
| `--color-bg-main` | `#ffffff` | <span style="display:inline-block; width:20px; height:20px; background-color:#ffffff; border:1px solid #ccc;"></span> | White |
| `--color-bg-header` | `#f0f0f0` | <span style="display:inline-block; width:20px; height:20px; background-color:#f0f0f0; border:1px solid #ccc;"></span> | Very light gray |
| `--color-bg-footer` | `#f0f0f0` | <span style="display:inline-block; width:20px; height:20px; background-color:#f0f0f0; border:1px solid #ccc;"></span> | Very light gray |
| `--color-link` | `#0066cc` | <span style="display:inline-block; width:20px; height:20px; background-color:#0066cc; border:1px solid #ccc;"></span> | Medium blue |
| `--color-link-hover` | `#004499` | <span style="display:inline-block; width:20px; height:20px; background-color:#004499; border:1px solid #ccc;"></span> | Dark blue |
| `--color-button` | `#0066cc` | <span style="display:inline-block; width:20px; height:20px; background-color:#0066cc; border:1px solid #ccc;"></span> | Medium blue |
| `--color-button-hover` | `#004499` | <span style="display:inline-block; width:20px; height:20px; background-color:#004499; border:1px solid #ccc;"></span> | Dark blue |

## Additional Styling Files
In addition to the theme variables, the application includes several other CSS files for specific components:

- `public/css/auth.css`: Styles for authentication pages
- `public/css/content.css`: Styles for main content areas
- `public/css/docs.css`: Styles for documentation pages
- `public/css/footer.css`: Styles for the footer
- `public/css/header.css`: Styles for the header
- `public/css/starry-background.css`: Styles for the starry background effect
- `public/css/admin/users/api-keys.css`: Styles for API keys management pages

## Best Practices
1. **Always use theme variables**: Instead of hardcoding colors, always use the theme variables to ensure consistency and make future theme changes easier.
2. **Maintain contrast**: When customizing the theme, ensure that text remains readable by maintaining sufficient contrast between text and background colors.
3. **Test changes**: After making theme changes, test the application on different devices and browsers to ensure compatibility.
4. **Document custom themes**: If you create custom themes, document the color schemes for future reference.

## Related Documentation
- [Views Documentation](../Views.md) - Documentation for Blade templates and view structure
