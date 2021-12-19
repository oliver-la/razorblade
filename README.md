# razorblade
Standalone blade template engine (8.0 spec)

This is currently work in progress. The goal is to replicate the functionality outlined in the [8.x spec](https://laravel.com/docs/8.x/blade) as much as possible, as a standalone version without any dependencies.

I'll use this for WordPress themes (integration instructions will be posted later), and I really don't want to deal with composer for wordpress projects. Causes more issues than it solves..

## Razorblade vs BladeOne

- Razorblade follows the 8.x spec and doesn't add custom directives or functionality
- Razorblade supports <x-component></x-component>
- BladeOne is better supported and battle tested longer than this project was

## Unsupported methods (and won't ever be)

- `view()` -> please use `->render()`
- `Blade::withoutDoubleEncoding();`
- Any `Illuminate\Support` (including Fascades `Js::from`) classes, and other Laravel specific methods
- `@auth` and `@guest` (you can still implement them yourself if required)
- `@production` and `@env` (you can still implement them yourself if required)
- Component Classes, Component Methods and Component Namespaces (Razorblade supports standalone component files only)
- Service injection
- All form methods (you can still implement them yourself if required)
