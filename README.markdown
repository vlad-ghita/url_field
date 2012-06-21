URL Field
======================

Specialized field for attaching internal cool links or external links in Symphony CMS.


## 1 About ##

This field depends on Entry URL field. Install it before continuing.

It allows the cool linking between Symphony entries so you will never have dead links any longer.

1\_ Add an Entry URL field named `View on site` to a `Section A`. Create some entries.

Set a slick value for `Anchor Label`: `Link of "{entry/title}"`<br />
Set a slick value for `Anchor URL`: `/entries/{//title/@handle}`

2\_ Go to `Section B` and add URL Field to it. Select `View on site` from `Section A` in `Values` selectbox.

3\_ Create an entry in `Section B`. You can swtich between `Internal` and `External` links. Internal select will
be populated with all entries from `Section A`.

For External links, the value must be a valid URI.

If you don't select any sections in field settings, the field will accept only External links.


## 2 Installation ##

0. Install Entry URL field.
1. Upload the 'url_url' folder in this archive to your Symphony 'extensions' folder.
2. Enable it by selecting the "Field: URL", choose Enable from the with-selected menu, then click Apply.
3. The field will be available in the list when creating a Section.
