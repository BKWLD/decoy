# Image

Decoy has an polymorphic `Image` model that should be used to store all model images.

## Setup

Add the `Bkwld\Decoy\Models\Traits\HasImages` trait to models that have images:

```php
class Article extends Base {
  use \Bkwld\Decoy\Models\Traits\HasImages;
}
```

## Usage

From the frontend, you can use the `img()` helper provided by the trait that was added to access a particular `Image` for your model.  Then, chain on one of the `Image` accessors.  For example:

```php
$article->img()->url; # Shorthand for $article->img('image')->url;
# /uploads/1/1/image1.jpg

$article->img('marquee')->crop(400,200)->url
# /uploads/1/1/image2-400x200.jpg

$article->img('marquee')->crop(400,200)->bkgd
# /background-image: url('/uploads/1/1/image2-400x200.jpg');background-position: 20% 30%;

$article->img('marquee')->crop(400,200)->tag
# <img src="/background-image: url('/uploads/1/1/image2-400x200.jpg');" alt="I am alt">

$article->img('marquee')->crop(400,200)->div->class('marquee')
# <div style="background-image: url('/uploads/1/1/image2-400x200.jpg');background-position: 20% 30%;" role="img" aria-label="I am alt" class="marquee"></div>
```

If no image exists, the response will be an empty string.  For instance:

```php
$article->img('fake')->crop(400,200)->tag
#
```

#### Validation

Add images to the `$rules` on a model like:

```php
$rules = [
  'images.default' => 'required',
  'images.listing' => 'required|mimes:png'
]
```

#### JSON

The `Bkwld\Decoy\Collections\Base` collection that all models return adds some helpers for adding cropped images to models before they get serialized.  See the [model docs](model).
