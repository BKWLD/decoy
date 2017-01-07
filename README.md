# Decoy

- Documentation: http://docs.decoy.bukwild.com

## About

Since 2012 and Laravel 3, [Bukwild](http://www.bukwild.com) has been maintaining our own content management system that we call [Decoy](http://www.bukwild.com/project/decoy). Decoy is meant to reduce the development effort for implementing content management while being highly flexible.  And it helps you generate a great looking, easy to use admin interface; no manual required.

The driving philosophy behind Decoy is that a CMS should share models with your public facing app; your app interacts with data stored with Decoy only through standard Laravel models.  This approach makes reading controller code simple (`Article::ordered()->take(6)->get()`), gives you access to Laravel mutators so you views are human readable (`$article->full_date`), and allows you to share business logic between admin and public sites.  There is no additional templating or querying language to learn.  After the CMS is setup, the developer interacts with the data using purely Laravel APIs.


#### Turn-key Decoy features

Besides it’s model-centric paradigm, Decoy includes the following features:

- Great looking, zero-training required design
- [Croppa](https://github.com/BKWLD/croppa) integration for advanced image support including cropping, setting focal point, and storing alt text
- Video encoding via [Zencoder](https://zencoder.com/) integration
- WYSIWYG text editing via [Redactor](https://imperavi.com/redactor/)
- Support for all Laravel relationship types
- Easy creation of content editing forms using [Former](http://formers.github.io/former/)
- Remote file storage (S3, etc) via [Upchuck](https://github.com/BKWLD/upchuck) and [Flysystem](http://flysystem.thephpleague.com/)
- Granular admin permissioning
- Fully customizable edit views with [Bootstrap](http://getbootstrap.com/) support
- Commands panel for executing artisan commands via web UI
- Cloning of content, even across servers
- Localization
- Organized key-value pair type data as [Elements](elements)
- Drag and drop ordering of data in list views
- Built in interface for creating 301 and 302 redirects
- All configuration stored in the filesystem to keep your team in sync via Git (or whatever VCS)


#### A familiar implementation paradigm: MVC

Decoy installs into your app as a composer package and integrates with your project rather than being a standalone install.  Decoy shares the Eloquent models of your public site and stores data in regular Laravel migrated tables.  Your controllers and views do not need to touch Decoy at all and while your models need to use Decoy’s subclass of `Eloquent\Model`, there is very little behavior added at model instantiation.  In other words, Decoy adds almost no overhead to your public site.

Implementing Decoy to manage model is done through a common MVC pattern that allows for overriding of default behavior at every step:

- The admin nav, permission levels, localization options, and other settings are stored in Laravel-style php config arrays
- For each manageable database table / Eloquent model, a Laravel-style controller is created that allows you to override defaults like how the title of the model appears, it’s description, how it can be searched within the admin, and even override any CRUD method (create, update, destroy, etc).
- You specify validation rules, ordering scopes, and features like which relationships to follow when cloning from the model, adding specifically named properties and methods.
- Finally, you create a regular Laravel view containing the form that should be shown to the admin during creation and editing of content.  This is easier than it sounds through support of Former and many Decoy-unqiue Former fields like our image uploader and wysiwyg types.


#### Next steps

Interested in giving Decoy a spin?  Check out the [quick start guide](http://docs.decoy.bukwild.com/quick-start).
