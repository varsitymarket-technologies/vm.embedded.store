# How To Create Your Own Shop Template

This guide explains how to structure your theme files so our system can render your shop correctly.

---

## 1. `poster.png`
This file is the **thumbnail** for your theme. It is essential for showcasing your design in the template marketplace or admin dashboard. 

## 2. `interface`
This is the core HTML file used by the system to generate the live website. It uses a specific anchor syntax: `e(__ANCHOR_NAME__)`.

### `interface` Example
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>e(__SITE_TITLE__)</title>
    
    <link href="[https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/dist/css/bootstrap.min.css](https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/dist/css/bootstrap.min.css)" rel="stylesheet">
    
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            /* Anchors used inside CSS */
            background-color: e(__DESIGN_COLOR_BACKGROUND__);
            color: e(__DESIGN_COLOR_TEXT__);
        }
        .text-primary {
            color: e(__DESIGN_COLOR_PRIMARY__) !important;
        }
    </style>
</head>
<body>

    <div class="container text-center">
        <div class="card shadow-sm p-5">
            <h1 class="text-primary">e(__SHOP_INTRO__)</h1>
            <p class="lead">e(__SHOP_DESCRIPTION__)</p>
            <hr>
            <p>e(__SHOP_PARAGRAPH__)</p>
            
            <div class="d-grid gap-2 d-md-block">
                <button class="btn btn-primary" type="button">Shop Now</button>
                <button class="btn btn-outline-secondary" type="button">Contact Us</button>
            </div>
        </div>
    </div>

    <script src="[https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js](https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js)"></script>
</body>
</html>
```

### System Anchors 

####  Website Anchors 
Use the following tags to allow the system to inject dynamic user content into your template.

#### Website Identity (__SITE_)
*   `e(__SITE_TITLE__)`: The main title of the website (used in <title>).
*   `e(__SITE_LOGO__)`: The URL or path to the website logo..

#### Shop Branding (__SHOP_)

*   `e(__SHOP_INTRO__)`: "The hero header text (e.g., ""Welcome to My Store"")."
*   `e(__SHOP_DESCRIPTION__)`: A short subheading or tagline.
*   `e(__SHOP_PARAGRAPH__)`: A longer body text description of the shop.

#### Visual Design (__DESIGN_)
These anchors allow users to customize the color palette of your template.

*   `e(__DESIGN_COLOR_BACKGROUND__)`: Main background color.
*   `e(__DESIGN_COLOR_TEXT__)`: Primary font color.
*   `e(__DESIGN_COLOR_PRIMARY__)`: Accent color for buttons and headings..

#### System Anchors (__SYSTEM_)
These anchors are used by the system to handle sensitive system points. 

*   `e(__SYSTEM_CURRENCY__)`: Place it where the system currency will be used.
*   `e(__SYSTEM_API__)`: This will be used in the javascript tags to redirect the functions to the appropriate js api.


## 4. `autofill.json`
This file is required. It provides fallback data in case a user hasn't configured specific settings yet. This ensures your template never looks "broken" during the initial setup.

#### `autofill.json` Example
```json
{   
    "__SITE_TITLE__": "My New Shop",
    "__SHOP_INTRO__": "Welcome to our Store",
    "__SHOP_DESCRIPTION__": "Quality products, delivered to you.",
    "__SHOP_PARAGRAPH__": "Start browsing our catalog to find the best deals today.",
    "__DESIGN_COLOR_TEXT__": "#212529",
    "__DESIGN_COLOR_BACKGROUND__": "#f8f9fa",
    "__DESIGN_COLOR_PRIMARY__": "#0d6efd"
}
```
Note: Ensure your JSON keys match the anchor names used in your interface file exactly.