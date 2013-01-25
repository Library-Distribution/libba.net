# Stylesheets using SCSS
This site is now using SCSS stylesheets.
SCSS extends CSS3 syntax and is converted to CSS before the site is published.
Read more on SCSS on [its homepage](http://sass-lang.com).

## Naming
Each SCSS file is named exactly as the generated CSS file:
`default.scss` &rArr; `default.css`

## Command line
### One-time generation:
```
sass --style compressed style:style
```

### Development
```
sass --style compressed --watch style:style
```
This regenerates the CSS each time a SCSS file is changed.
The compression style can be omitted or changed here.