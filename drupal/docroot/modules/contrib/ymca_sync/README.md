# YMCA Sync module

Provide uniform program interface for run drush commands without race condition

## How to add new syncer

Add to your module_name.service.yml code like this:

```yaml
  my_data.syncer:
    class: Drupal\ymca_sync\Syncer
    arguments: []
    calls:
      - [addStep, ['@my_service', 'my_method']]
      - [addStep, ['@my_another_service', 'my_another_method']]
    tags:
      - { name: syncer }
```

Clear cache. Go to `/admin/config/system/ymca-sync` and activate you syncer

## How list syncers from command-line

run `drush yn-sync:list`

## How to run your syncer

Run syncer by drush commands, for example:
```sh
drush yn-sync my_data.syncer
```
If your run syncer twice at the same time on second run you see message like this:
```sh
Lock syncer my_data.syncer is still working. Exit.
```

