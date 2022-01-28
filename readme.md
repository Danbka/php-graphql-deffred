### Memory issue with using Deffered

#### run:
``composer install``


``cd public``

``php -S localhost:8000``

Send the request:
```json
query {
  getBooks {
    title
    author {
      name
    }
  }
}
```

and have a look at the console. You will se something like this:
`Used memory: 58 Mb`

After the second request it will be around
`Used memory: 30`
then `Used memory: 16` and so on.

Now comment 53-55 lined in `index.php` and comment out line 56
```php
return new Deferred(function() use ($authors, $rootValue) {
    return $authors[$rootValue['authorId']];
});
// return $authors[$rootValue['authorId']];
```

send the same request and the memory usage much less:
`Used memory: 4 Mb`