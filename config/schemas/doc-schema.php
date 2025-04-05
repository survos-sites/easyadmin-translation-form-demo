<?php // config/schemas/blog.php

use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;

return new Index('blog', [
    'key' => new Field\IdentifierField('key'),
    'title' => new Field\TextField('title'),
    'description' => new Field\TextField('description'),
//    'tags' => new Field\TextField('tags', multiple: true, filterable: true),
]);
