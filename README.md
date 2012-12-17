RESTcomic API
=============
An attempt at a RESTful API for a webcomic management system for a databases course. Outputs JSON.

##Libraries used
* [Slim framework](http://www.slimframework.com/) for route handling
* [PHPass](http://www.openwall.com/phpass/) for password hashing/validation
* [PHP Markdown](http://michelf.ca/projects/php-markdown/) for posts
* Andy Smith's [PHP OAuth](http://oauth.googlecode.com/svn/code/php/) for API authorization

##Dependencies
* PHP>=5.3
* mySQL
* Apache with mod_rewrite enabled

##Installation
* Edit the config file (`includes/config.php`) with the relevant database details
* Upload to the server (make sure to init/update Slim submodule)
* Navigate over to `install.php` on the server
    * It replaces existing tables with the same name, and adds some default users/groups
    * You might want to remove this file when it's done installing

##Known Bugs
I don't expect anyone to actually use this, but consider the following:

* Security is probably not that great. 
    * Nonces are not considered anywhere; replay attacks are possible.
    * A user's email can be obtained pretty easily
* Hidden posts/comments don't work very well. 
    * They are currently hidden from everyone, including admins and the users who made the post/comment.
    * Messes up pagination, which doesn't output a "next" link if the number of results is less than the `perpage` parameter
* Pagination isn't great either.
    * If the number of results is a multiple of the `perpage` parameter, there'll be a link to a page that has no results.
* There's no way to get a user's API key except through the database directly

----------------------------------------
Routes
======
The following examples are relative to the base path of the API. Use 2-legged OAuth for POST/PUT/DELETE requests (public key is the user's login, shared secret is the user's API key).

#Example Routes
* `GET /`
- - -
#Posts
* `GET /posts`
* `GET /posts/id/:ids` 
    * comma separated list of post IDs
* `GET /posts/:slug` 
    * title slug of the post
* `GET /posts/by_author/:login` 
    * author login
* `GET /posts/by_author/id/:id` 
    * author ID
* `GET /posts/tagged/:tags` 
    * comma separated list of tags
* `POST /posts`
* `PUT /posts/id/:id` 
    * ID of the post
* `DELETE /posts/id/:id`
    * ID of the post

##POST/PUT parameters
* `post_id` 
    * 0 for POST
* `user_id`
* `title`
* `title_slug` 
    * Leave blank to generate new slug or use existing
* `status`
    * 0: visible
    * 1: scheduled (doesn't work)
    * 2: hidden
* `commentable`
    * boolean
* `timestamp`
    * Whatever can be read by PHP's `convertDatetime()`
* `image_url`
* `content`
    * In markdown format
* `tags`
    * Comma-separated list of tags

###Optional Query Parameters
* `perpage={0,1,2,...}`
* `page={1,2,...}`
* `reverse={true|false}`
* `format={markdown|html}`
* `from={0,1,2,...}` 
    * Used in `GET /posts`, start from ID
* `to={0,1,2,...}` 
    * Used in `GET /posts`, list until ID

- - -
#Comments
* `GET /comments`
* `GET /comments/id/:ids` 
    * comma separated list of comment IDs
* `GET /comments/by_author/:login`
    * author's login
* `GET /comments/by_author/id/:id` 
    * author's user ID
* `GET /posts/slug/comments` 
    * title slug of the post
* `GET /posts/id/:id/comments` 
    * ID of the post
* `POST /posts/id/:id/comments` 
    * ID of the post
* `PUT /comments/id/:id` 
    * ID of the comment
* `DELETE /comments/id/:id` 
    * ID of the comment

##POST/PUT parameters
* `comment_id`
    * 0 for POST
* `post_id`
   * disregarded if `parent_comment_id` is set
* `user_id`
    * 0 for unregistered user
* `parent_comment_id`
    * leave 0 if not a reply
* `timestamp`
    * Whatever can be read by PHP's `convertDatetime()`
* `ip`
* `visible`
    * boolean
* `content`
* `name`
    * Mostly just for unregistered users

###Optional Query Parameters
* `perpage={0,1,2,...}`
* `page={1,2,...}`
* `reverse={true|false}`
* `from={0,1,2,...}` 
    * Used in `GET /comments`, start from ID
* `to={0,1,2,...}` 
    * Used in `GET /comments`, list until ID

- - -
#Users
* `GET /users/:login`
    * user's login
* `GET /users/id/:id` 
    * user's ID
* `POST /users`
* `PUT /users/id/:id` 
    * user's ID
* `DELETE /users/id/:id` 
    * user's ID

##POST/PUT parameters
* `user_id`
    * 0 for POST
* `group_id`
    * 2 for POST
* `login`
    * leave blank for PUT
* `name`
* `password`
    * PUT: leave blank to keep the same
    * POST: you probably don't want to call the API to register... do it locally
* `date_registered`
    * Whatever can be read by PHP's `convertDatetime()`
* `email`
    * Used for [gravatar](http://gravatar.com)
* `website`

###Optional Query Parameters
* `getemail={true|false}`

- - -
#Groups
* `GET /groups`
* `GET /groups/id/:ids` 
    * comma separated list of group IDs
* `POST /groups`
* `PUT /groups/id/:id` 
    * group's ID
* `DELETE /groups/id/:id` 
    * group's ID

##POST/PUT parameters
* `group_id`
    * 0 for POST
* `name`
* `color`
    * e.g., `#c0ffee`
* `admin_perm`
    * boolean
* `make_post_perm`
    * 0: not allowed
    * 1: hidden by default
    * 2: allowed
* `edit_post_perm`
    * 0: not allowed
    * 1: own posts
    * 2: group's posts
    * 3: all posts
* `make_comment_perm`
    * see `make_post_perm`
* `edit_comment_perm`
    * see `edit_post_perm`


