# Concrete CMS core

This repository contains the `concrete` directory of the [concretecms/concretecms](https://github.com/concretecms/concretecms) repository, retaining the git history.

Its branches and tags are generated automatically: please don't push to them, and submit your pull requests to [concretecms/concretecms](https://github.com/concretecms/concretecms).

## The `core-splitter` branch

The `core-splitter` branch is the only one that's not generated automatically: it contains the GitHub Actions workflow that generates the other ones.

The split is performed by [incremental-git-filterbranch](https://github.com/concretecms/incremental-filter-branch).

### How it works

1. Someone pushes a branch or a tag to [concretecms/concretecms](https://github.com/concretecms/concretecms).
2. The `Notify splitter` workflow of that repository (`.github/workflows/notify-splitter.yml`) starts the `Split` workflow of this repository, by using a token.
3. The `Split` workflow of this repository (`.github/workflows/split.yml`) processes the new commits and tags.
4. The `Split` workflow pushes the result to this repository, by using the token provided by GitHub Actions.

The `Split` workflow can also be started manually, from the [Actions tab](https://github.com/concretecms/concretecms-core/actions/workflows/split.yml) of this repository.

The data required by the incremental runs is stored in the GitHub Actions cache.
If the cache is not available, the whole source repository is processed again: it takes much longer, but the resulting commits are exactly the same.

### Setup

1. The `core-splitter` branch must be the default branch of this repository: GitHub only runs the workflows started by events, by the schedule, or manually if they are in the default branch.
2. The `Notify splitter` workflow requires an access token:
   1. Create a new fine-grained personal access token:
      - go to https://github.com/settings/personal-access-tokens/new
      - Token name: for example `core_splitter`
      - Resource owner: `concretecms` (the organization must allow fine-grained personal access tokens, and an owner may have to approve the token)
      - Expiration: choose an expiration date, and remember to renew the token before it expires
      - Repository access: `Only select repositories`, and select `concretecms/concretecms-core`
      - Permissions: `Actions` > `Read and write` (it's the permission required to start a workflow)
      - copy the generated token (`github_pat_...`): it's displayed only once
   2. Save the token as a secret of the source repository:
      - go to https://github.com/concretecms/concretecms/settings/secrets/actions
      - click `New repository secret`
      - Name: `SPLITTER_TOKEN`
      - Secret: the generated token

### Remarks

- The `Notify splitter` workflow must be present in every branch of concretecms that should start the split: GitHub runs the workflows of the branch that receives the push.
- A new tag starts the split only if its commit contains the `Notify splitter` workflow.
- Deleted branches and tags are handled by the `Notify splitter` workflow of the default branch of concretecms.
- Only one split runs at a time. If other splits are requested in the meantime, only the most recent one waits: the other ones are cancelled by GitHub. That's not a problem, since every run processes all the new commits and tags.
- The branches and the tags to be processed are defined by the `BRANCH_WHITELIST` and `TAG_BLACKLIST` variables in `.github/workflows/split.yml`. The `Notify splitter` workflow reads them from there, so that it doesn't start the split for the other branches and tags.
- The version of incremental-git-filterbranch to be used is defined by the `INCREMENTAL_FILTER_BRANCH_VERSION` variable in `.github/workflows/split.yml`.
- To process the whole source repository again (for example after upgrading to a version of incremental-git-filterbranch that fixes the way commits are rewritten), start the `Split` workflow manually, checking the `from-scratch` option. Please remark that it takes about 20 minutes.
- For debugging purposes, you can download the working directory used by a run: start the `Split` workflow manually, checking the `save-work-directory` option. You'll find it in the artifacts of the run, for 3 days.
- The `Split` workflow also runs every Monday and Thursday at 10:30 UTC (that's late at night in Portland), to keep the cache alive (GitHub deletes the caches that haven't been used for 7 days), to process the branches and tags that didn't start it, and to optimize the data stored in the cache (it takes some minutes). GitHub disables scheduled workflows in repositories without commits for 60 days: in that case you'll receive an email, and you can enable it again from the Actions tab.
