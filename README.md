# Liip Serializer 

**This project is Open Sourced based on work that we did initially as closed source at Liip, it may be lacking some documentation. If there is anything that you need or have questions about we would love to see you open an issue! :)**

The serializer generator produces simplistic PHP functions that handle the conversion between arrays and objects for a
specific combination of serializer groups and one API version. To do this, the generator uses the JMS serializer
annotations on the models. You need to specify which models should have such PHP functions. In the beginning, we do this
for Products only.

The serializer is made available as the class GeneratedSerializer which implements the JMS interfaces. If no generated
file is found or if any error occurs, that serializer falls back to JMS.

When finished, we'd love to open source it for others to take advantage of the performance boosts too!

## Implementation Overview

The code hopefully speaks for itself. You can look at every file here to see what it does but here is a simple overview: 
- The `PropertyMetadata` model holds the annotations we support;
- The `Parser` parses the JMS annotations and builds the PropertyMetadata;
- The `DeserializerGenerator` and `SerializerGenerator`, produces PHP code from the metadata.
  They have some things in common - to avoid duplication they both extend from the abstract generator.
  The generators use twig to render the PHP code, for better readability.
  The indentation in the generated code is not respecting levels of nesting. We could carry around the depth and prepend
  whitespace, but apart from debugging, nobody will look at the generated code.
- The `Compiler` ties everything together:
    - It calls the `Parser` to read JMS metadata into `PropertyMetadata`. It produces a hashmap of class FQN to a
      second hashmap per class with JSON field name to `PropertyMetadata`;
    - It calls the `SerializerGenerator` for the specified API versions and group combinations. The generated files
      follow the naming convention of `serialize_FQN_WITH_UNDERSCORES_group_group_versionnumber.php`;
    - It calls the `DeserializerGenerator`. As we do never deserialize with groups or API version, we only
      support the default deserialization. The filename convention is `serialize_FQN_WITH_UNDERSCORES.php`.
- There is a hack to work around the recursive model structure in `Recursion`.

We decided to not use reflection, for better performance. Properties need to be public or have a public getter for
serialization. For deserialization, we also match constructor arguments by name, so as long as the property name matches
a constructor argument, they need no setter.

## Where do I go for help?

If you need help, open an issue. 

## Why an object serializer generator

The first experiment was a Golang serializer. It is a lot faster than JMS serializer. However, integrating that with PHP
is some pain, and we would either need some hybrid solution or move a lot application logic into Golang.

We experimented and found that using the same concept but with PHP still brings a significant performance gain.

The PoC gave the following results:
* Overall performance gain: 55%, 390 ms => 175 ms
* CPU and I/O wait both down by ~50%, Memory gain: 21%, 6.5 MB => 5.15 MB
