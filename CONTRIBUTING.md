# Contributing

Contributions are **welcome** and will be fully **credited**.

We accept contributions via Pull Requests on [Github](https://github.com/alchemyguy/YoutubeLaravelApi).


## Pull Requests

- **[PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)** - The project uses [Laravel Pint](https://laravel.com/docs/pint) (Laravel preset, PSR-12-based). Check style with `composer lint` and apply fixes with `composer fix`.

- **Static analysis** - Run `composer analyse` (PHPStan, level 8). All new code must keep analysis clean.

- **Add tests!** - Your patch won't be accepted if it doesn't have tests. The suite uses [Pest](https://pestphp.com). Run unit tests with `composer test:unit` and the full suite with `composer test`.

- **No local PHP? Use Docker.** A `Makefile` is provided so you can run everything in containers without installing PHP locally:

  ```bash
  make lint          # composer lint
  make fix           # composer fix
  make test-unit     # composer test:unit
  make analyse       # composer analyse
  ```

- **Document any change in behaviour** - Make sure the `README.md`, the `docs/` site, and any other relevant documentation are kept up-to-date.

- **Consider our release cycle** - We try to follow [SemVer v2.0.0](http://semver.org/). Randomly breaking public APIs is not an option.

- **Create feature branches** - Don't ask us to pull from your master branch.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](http://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.

CI runs lint, static analysis, and the test suite via GitHub Actions on every pull request.


**Happy coding**!
