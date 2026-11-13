# v1.1.0
##  13-11-2026

1. [](#new)
    * Declare compatibility with Grav 1.7 and 2.0
    * Add standalone shortcode smoke tests

2. [](#bugfix)
    * Update bundled highlight.php to 9.18.1.10 to avoid PHP 8.2+ dynamic property deprecations
    * Handle missing shortcode language and code parameters without PHP warnings or type errors

# v1.0.0
##  09-04-2022

1. [](#new)
    * Option to enable or disable syntax highlighting site-wide or per-page by
      default
    * Per-page option to enable or disable the plugin on a per-page basis

2. [](#improved)
    * Improve performance by registering a single, paramterized shortcode
      instead of registering each language automatically
    * More flexibility in asset loading strategies
    * Include sample override CSS stylesheet on plugin installation to show
      less-experienced users how the user overrides work
# v0.9.0
##  20-03-2022

1. [](#new)
    * First version of the plugin ready for consideration to be included in the
      Grav repository. (Works on my machine™️).