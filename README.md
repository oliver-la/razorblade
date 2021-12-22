# razorblade
Standalone blade template engine (8.0 spec)

This is currently work in progress. The goal is to replicate the functionality outlined in the [8.x spec](https://laravel.com/docs/8.x/blade) as much as possible, as a standalone version without any dependencies.

I'll use this for WordPress themes (integration instructions will be posted later), and I really don't want to deal with composer for wordpress projects. Causes more issues than it solves..

## Razorblade vs BladeOne

- Razorblade follows the 8.x spec and doesn't add custom directives or functionality
- Razorblade supports `<x-component></x-component>`
- BladeOne is better supported and battle tested longer than this project was
- BladeOne supports namespaces

## Unsupported methods (and won't ever be)

- `Blade::withoutDoubleEncoding();`
- Any `Illuminate\Support` (including Fascades `Js::from`) classes, and other Laravel specific methods
- `@auth` and `@guest` (you can still implement them yourself if required)
- `@production` and `@env` (you can still implement them yourself if required)
- Component Classes, Component Methods and Component Namespaces (Razorblade supports standalone component files only)
- Service injection
- All form methods (you can still implement them yourself if required)

## Test Cases

- [ ] {{ }}
  - [ ] Escaping works
  - [ ] Variables can be used
  - [ ] Functions can be called
  - [ ] Strings can be used ({{ 'hello' }})
  - [ ] Can be escaped by prefixing it with an at-sign
- [ ] {!! !!}
  - [ ] Does not escape anything
  - [ ] Variables can be used
  - [ ] Functions can be called
  - [ ] Strings can be used ({{ 'hello' }})
  - [ ] Can be escaped by prefixing it with an at-sign
- [ ] {{-- --}}
- [ ] General statement tests
  - [ ] Can be escaped using an at-sign
  - [ ] Are only parsed from files, never database or user supplied input
- [ ] Statements
  - [ ] verbatim
  - [ ] if
  - [ ] else
  - [ ] elseif
  - [ ] endif
  - [ ] unless
  - [ ] endunless
  - [ ] isset
  - [ ] endisset
  - [ ] empty
  - [ ] endempty
  - [ ] hasSection
  - [ ] sectionMissing
  - [ ] switch
  - [ ] case
  - [ ] break
  - [ ] default
  - [ ] endswitch
  - [ ] for
  - [ ] endfor
  - [ ] foreach
  - [ ] endforeach
  - [ ] forelse
  - [ ] empty
  - [ ] endforelse
  - [ ] while
  - [ ] endwhile
  - [ ] continue
  - [ ] continue with condition
  - [ ] break with condition
  - [ ] $loop variable
  - [ ] parent
  - [ ] countables
  - [ ] uncountables (while)
  - [ ] class
  - [ ] include
  - [ ] with args
  - [ ] includeif
  - [ ] with args
  - [ ] includeWhen
  - [ ] with args
  - [ ] includeUnless
  - [ ] with args
  - [ ] includeFirst
  - [ ] with args
  - [ ] each
  - [ ] with empty view
  - [ ] push
  - [ ] once
  - [ ] endpush
  - [ ] stack
  - [ ] component
  - [ ] XHTML syntax (`<x-button>`)
  - [ ] with attributes
  - [ ] merge attributes
  - [ ] append css classes
  - [ ] props
  - [ ] with args
  - [ ] slot
  - [ ] section
  - [ ] endsection
  - [ ] yield
  - [ ] show
  - [ ] parent
  - [ ] layout
  - [ ] slot
    
## ToDo

- [ ] Implement scoped slots (@props)
- [ ] Expose API to allow for custom statements
- [ ] Implement pipes
- [ ] Implement comment block
- [ ] Implement verbatim statement
