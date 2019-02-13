If you wish to contribute to Liip Serializer, feel free to do so. 

It is generally a good idea to open an issue before you create a Pull-Request to avoid wasting your time. That way the maintainers can ensure that the changes you want to make, is something that the project would welcome.

## What to work on
You can work on anything! All the current issues are listed on the GitHub issue tracker. If you don’t see the thing you want to work on there, please open a new issue. 

PR’s improving documentation and tests are always very welcome too.

## Pull-Request workflow
Change the code in your own fork/branch. When you believe the code is ready to be applied, submit the PR. 

Once you have submitted the PR, people will look at it and provide feedback.

If it cannot be applied in its current state, then a comment will be left and the PR will be closed.

In general, only submit a PR if you believe it can be applied in its current state. If you need an exception to this rule please use “WIP: “ in front of your PR to indicate that it is work in progress. This could be beneficial for instance if you’re looking for feedback while your PR is still in progress.

When you have time to address the comments left on your PR, please make the changes, push them to github and then re-open the pull request.

If you do not have time to address the changes, that’s OK. Just leave a comment in the PR and either someone else can pick up the changes, or the PR will be closed. 

## Coding Style & Decisions
We write tests for our code, if you add code, please also add tests for that code. We do not demand specific code coverage, but we want tests where they make sense.

In general this project uses the [Symfony coding standards](https://symfony.com/doc/current/contributing/co).

We have our own standards when it comes to PHPDoc, when in doubt use these rules over Symfony’s. 

### PHPDoc
It’s a good idea to mention GitHub issue numbers when there are hardcoded special cases or other business decisions in the code. Always aim to explain the why behind the how;
 
If there is something to explain about a class, use the class docblock and not the constructor. Constructors will rarely need explanation apart from parameter descriptions; 
 
Use the class docblock to explain both technical but also business WHY questions; 
 
Add the @var Type annotation on all properties of the class. The type is usually also clearly defined from the constructor and the assignment, but we find it more readable with the @var annotations; 
 
Do not use the @throws tag unless you have something very specific to say about it. We do not care to track exceptions flow through all the code. Disable the warning for this in your IDE if needed; 
 
Inline comments should be kept when they add value (for example reference GitHub issues, explain a regular expression, etc). But when the code is hard to read, consider refactoring by extracting code into private methods or separate classes. With good naming, such methods can often replace a code comment.

When in doubt, add a comment.

#### Parameter documentation 
Parameter documentation should explain the value range if there are restrictions further than the type. Use @param annotations if: 

* There is further information about the value range that is not obvious from the type. E.g. a string that can only be some specific constants, or @param int $priority From 0 to 10. The higher the number, the higher the priority. One special case is arrays, which can not be further declared in PHP; 
 
* Always specify the list element types of arrays with a docblock, e.g. @param string[] $list; 
 
* Only add documentation for parameters where there is something to add. "Incomplete" doc blocks are fine. If your IDE is complaining about this: Disable the warnings for docblocks. We have code sniffers to ensure our doc blocks do not contain wrong parameter names. 
 
#### Return type documentation 
 Use the : Type syntax, resp : ?Type for Type|null. Do not use @return unless an array is returned, or when mixed types are returned. 
