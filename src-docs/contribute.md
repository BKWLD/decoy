# Contribute

- The `master` branch represents what will be come the next **minor** release.

- A small, low-risk feature for an actively developed project should be created in a feature branch (based on the latest version-branch) and then merged into both the version-branch and master.

- A riskier feature should be worked on in a feature branch and then moved into master.  When it's finished, it can be come part of the next minor version release.  This git command gives you a nice view into commits that are new on master versus the most recent version (replace `{branch}` with the latest versioned-branch):

		bash git log --graph --pretty=format:'%Cred%h%Creset -%C(yellow)%d%Creset %s %Cgreen(%cr)%Creset' --abbrev-commit --date=relative {branch}..master

## Docs

To be able to update docs, you need to install mkdocs:

1. Install python (like via Homebrew)
2. Install mkdocs with `pip install mkdocs`

The source markdown files are in src-docs.  After making changes, run the comile instructions (below).  When adding new files, make sure to update the mkdocs.yml file to add pages to the navigation.

Here are some useful comands:

- `mkdocs build` - Compile changes into html files
- `mkdocs serve` - Watches for changes and creates a web endpoint to preview at
