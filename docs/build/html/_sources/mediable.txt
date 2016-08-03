Handling Media
==============

.. highlight:: php

Add the ``Mediable`` trait to any Eloquent models that you would like to be able to attach media to.

::

    <?php

    namespace App;

    use Illuminate\Database\Eloquent\Model;
    use Plank\Mediable\Mediable;

    class Post extends Model
    {
        use Mediable;

        // ...
    }

Attaching Media
--------------------------

You can attach media to your ``Mediable`` model using the ``attachMedia()`` method. This method takes a second argument, specifying one or more tags which define the relationship between the model and the media. Tags are simply strings; you can use any value you need to describe how the model should use its attached media.

::

    <?php
    $post = Post::first();
    $post->attachMedia($media, 'thumbnail');

You can attach multiple media to the same tag with a single call. The ``attachMedia()`` method accept any of the following for its first parameter:

- a numeric or string id
- an instance of ``\Plank\Mediable\Media``
- an array of ids
- an instance of ``\Illuminate\Database\Eloquent\Collection``

::

    <?php
    $post->attachMedia([$media1->getKey(), $media2->getKey()], 'gallery');

You can also assign media to multiple tags with a single call.

::

    <?php
    $post->attachMedia($media, ['gallery', 'featured']);


Replacing Media
--------------------------

Media and Mediable models share a many-to-many relationship, which allows for any number of media to be added to any key. The ``attachMedia()`` method will add a new association, but will not remove any existing associations to other media. If you want to replace the media previously attached to the specified tag(s) you can use the ``syncMedia()`` method. This method accepts the same inputs as ``attachMedia()``.

::

    <?php
    $post->syncMedia($media, 'thumbnail');

Retrieving Media
--------------------------

You can retrieve media attached to a file by refering to the tag to which it was previously assigned.

::

    <?php
    $media = $post->getMedia('thumbnail');

This returns a collection of all media assigned to that tag. In cases where you only need one `Media` entity, you can instead use `firstMedia()`.

::

    <?php
    $media = $post->firstMedia('thumbnail');
    // shorthand for
    $media = $post->getMedia('thumbnail')->first();

If you specify an array of tags, the method will return media is attached to any of those tags. Set the ``$match_all`` parameter to ``true`` to tell the method to only return media that are attached to all of the specified tags.

::

    <?php
    $post->getMedia(['header', 'footer']); // get media with either tag
    $post->getMedia(['header', 'footer'], true); //get media with both tags
    $post->getMediaMatchAll(['header', 'footer']); //alias

You can also get all media attached to a model, grouped by tag.

::

    <?php
    $post->getAllMediaByTag();

Checking for the Presence of Media
----------------------------------

You can verify if a model has one or more media assigned to a given tag with the ``hasMedia()`` method.

::

    <?php
    if($post->hasMedia('thumbnail')){
        // ...
    }

You can specify multiple tags when calling either method, which functions similarly to ``getMedia()``. The method will return ``true`` if ``getMedia()`` passed the same parameters would return any instances.

You also can also perform this check using the query builder.

::

    <?php
    $posts = Post::whereHasMedia('thumbnail')->get();

Detaching Media
--------------------------

You can remove a media record from a model with the ``detachMedia()`` method.

::

    <?php
    $post->detachMedia($media); // remove media from all tags
    $post->detachMedia($media, 'feature'); //remove media from specific tag
    $post->detachMedia($media, ['feature', 'thumbnail']); //remove media from multiple tags


You can also remove all media assigned to one or more tags

::

    <?php
    $post->detachMediaTags('feature');
    $post->detachMediaTags(['feature', 'thumbnail']);

Loading Media
--------------------------

When dealing with any model relationships, taking care to avoid running into the "N+1 problem" is an important optimization consideration. The N+1 problem can be summed up as a separate query being run for the related content of each record of the parent model. Consider the following example:

::

    <?php
    $posts = Post::limit(10)->get();
    foreach($posts as $post){
        echo $post->firstMedia('thumbnail')->getUrl();
    }

Assuming there are at least 10 Post records available, this code will execute 11 queries: oen query to load the 10 posts from the database, then another 10 queries to load the media for each of the post records indiviudally. This will slow down the rendering of the page.

There are a couple of approaches that can be taken to preload the attached media in order to avoid this issue.

Eager Loading
^^^^^^^^^^^^^^

The Eloquent query builder's ``with()`` method is the prefered way to eager load related models. This package also provides an alias.

::

    <?php
    $posts = Post::with('media')->get();
    // or
    $posts = Post::withMedia()->get();

You can also load only media attached to specific tags.

::

    <?php
    $posts = Post::withMedia(['thumbnail', 'featured']); // attached to either tags
    $posts = Post::withMediaMatchAll(['thumbnail', 'featured']); // attached to both tags

**Note**: if using this approach to conditionally preload media by tag, you will not be able to access media with other tags using ``getMedia()`` without first reloading the media relationship on that record.

Lazy Eager Loading
^^^^^^^^^^^^^^^^^^^

If you have already loaded models from the database, you can still load relationships with the ``load()`` method of the Eloquent Collection class. The package also provides an alias.

::

    <?php
    $posts = Post::all();
    // ...

    $posts->load('media');
    // or
    $posts->loadMedia();


You can also load only media attached to specific tags.

::

    <?php
    $posts->loadMedia(['thumbnail', 'featured']); // attached to either tag
    $posts->loadMediaMatchAll(['thumbnail', 'featured']); // attached to both tags


The same method is available as part of the Mediable trait, and can be used directly on a model instance.

::

    <?php
    $post = Post::first();
    $post->loadMedia();
    $post->loadMedia(['thumbnail', 'featured']); // attached to either tag
    $post->loadMediaMatchAll(['thumbnail', 'featured']); // attached to all tags

**Note**: if using this approach to conditionally preload media by tag, you will not be able to access media with other tags using ``getMedia()`` without first reloading the media relationship on that record.
