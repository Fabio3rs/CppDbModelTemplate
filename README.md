# Generate db model
### Generate mysql database model from template and json
```
php model_parse.php "template.cpp.php" "YourModelJson.json" | clang-format > YourModelFile.cpp
php model_parse.php "template.hpp.php" "YourModelJson.json" | clang-format > YourModelFile.hpp
```


