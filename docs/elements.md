# Elements

Copy, images, and files that aren't managed as part of an item in a list.  If content needs to be managed and a model doesn't make sense, use Elements.

![](assets/img/elements.png)

## Setup

Begin by customizing the `config/decoy/elements.yaml` file that will have been published during the Decoy installation.  Roughly speaking, there are 3 nested layers of hashes that configure elements:

- A page
  - A section
    - A field

The syntax has a terse form:

```
homepage:
  marquee:
    image,image: /img/temp/home-marquee.jpg
```

And an expanded form:

```
homepage:
  label: The homepage
  help: This is the site homepage
  sections:
    marquee:
      label: Home marquee
      help: The featured image section on the top of homepage
      fields:
        image:
          type: image
          label: An image
          value: /img/temp/home-marquee.jpg
```

The two forms can be intermixed. Check out the `elements.yaml` file for more examples.

Alternatively, you can create a directory at `config/decoy/elements` and create many different .yaml files within there. They all share the same syntax as the main `elements.yaml` and get merged into recursively merged into one another.

## Usage

Call `Decoy::el('key')` in your frontend views to return the value for an Element.  They key is the `.` concatented keys for the three heirachies: `page.section.field`.  The value will be massaged in different ways depending on the element type:

- Texteareas will have `nl2br()` applied
- WYSIWYG will be wrapped in a `<p>` if there is no containing HTML element
- Images will be copied out of the /img directory and into /uploads

#### Additional notes

- The default format for a field in the admin is a text input
- Images **must** be stored in the /public/img directory.  Decoy will automatically make a copy in the uploads directory for Croppa to act on.  Decoy::el() will then return the path to the uploads copy.  This is done because PagodaBox doesn't let you push things via git to shared writeable directories, so committing the image to the uploads dir would not work.
- YAML only allows whitespace indenting, no tabs
- You can use `Image` `crop()` on `image` fields.  For instance: `Decoy::el('home.intro.image')->crop(500)->url`
