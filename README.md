# Liip Serializer - A "drop in" replacement to JMS serializer

**This project is Open Sourced based on work that we did initially as closed source at Liip, it may be lacking some documentation. We plan to add more documentation and support, including Symfony bundles in the near future. If there is anything that you need or have questions about we would love to see you open an issue! :)**

# What it supports
This serializer can convert between JSON and PHP objects and back. It uses reflection, Phpdoc and [JMS Serializer](https://github.com/schmittjoh/serializer/) annotations to generate PHP code for the conversion. JMS serializer groups and versions are supported for serializing but not for deserializing.

## Limitations
If you customized JMS Serializer with your own listeners or similar things, this serializer will not work for you. We made an effort to detect when unsupported features are used and raise an error, but recommend that you double check whether the Liip Serializer really produces the exact same as JMS when transforming your data.

# How it works
The Liip serializer generates PHP code based on the PHP models that you specify. It uses the flexible `liip/metadata-parser` to gather metadata on the models. A separate file is generated for every version and serializer groups combination to move all logic to the code generation step. This serializer is fast because the generated PHP code is very simplistic and specific to the usecase, rather than the complex, flexible callback structure that JMS serializer uses.

# How to use it
We plan to make this easier in the very near future, and if you use Symfony, we plan to create a bundle for this!

You need to generate files and can point the `GeneratedSerializer` to the folder with the generated files. Because the files are generated, you need to specify the exact list of classes, serializer groups and versions you want to support.

## Generate your files
This step needs to be executed during the deployment phase and whenever your models change.

```php
$classMetaData = [
    Product::class => [
        ['api'],
        ['api', 'product-details'],
    ],
    User::class => [
        ['api'],
    ],
];

$versions = ['1', '2', '4'];

$builder = new Builder(new Parser($parsers), new RecursionChecker(null, []));
$templating = new Serialization();

$serializerGenerator = new SerializerGenerator($templating, $versions, $classMetaData, $cacheDirectory);
$deserializerGenerator = new DeserializerGenerator($templating, [Product::class, User::class], $cacheDirectory);
$serializerGenerator->generate($builder);
$deserializerGenerator->generate($builder);
```

## Serialize using the generated code
In this example, we serialize an object of class `Product` for version 2:

```
// There is a fallback to JMS; pass that in here as $originalSerializer. Along with the directory of your files, and a logger.
$generatedSerializer = new GeneratedSerializer($originalSerializer, $cacheDirectory, $logger);

// A model to serialize
$productModel = new Product();

// Your serialized data
$data = $generatedSerializer->serialize($productModel, 'json', SerializationContext::create()->setVersion(2));
```

## Deserialize using the generated code
```
// There is a fallback to JMS; pass that in here. Along with the directory of your files, and a logger.
$generatedSerializer = new GeneratedSerializer($originalSerializer, $cacheDirectory, $logger);

// Data to deserialize
$data = [
    'api_string' => 'api',
    'detail_string' => 'details',
    'nested_field' => ['nested_string' => 'nested'],
    'date' => '2018-08-03T00:00:00+02:00',
    'date_immutable' => '2016-06-01T00:00:00+02:00',
];

/** @var Product $model */
$model = $generatedSerializer->deserialize($data, Product::class, 'json');
```

# Where do I go for help?
If you need help, please open an issue on github.

# Background: Why an Object Serializer Generator?
We started having performance problem with large object structures (often
several Megabyte of JSON data). Code analysis showed that a lot of time is
spent calling the JMS callback structure hundred thousands of times.
Simplistic generated PHP code is much more efficient at runtime.

# Implementation Notes
The `DeserializerGenerator` and `SerializerGenerator` produce PHP code from the
metadata. The generators use twig to render the PHP code, for better
readability. See the `Template` namespace.

The indentation in the generated code is not respecting levels of nesting. We
could carry around the depth and prepend whitespace, but apart from debugging,
nobody will look at the generated code.

We decided to not use reflection, for better performance. Properties need to be
public or have a public getter for serialization. For deserialization, we also
match constructor arguments by name, so as long as a non-public property name
matches a constructor argument, it needs no setter.
