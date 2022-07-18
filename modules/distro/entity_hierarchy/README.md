# Entity Hierarchy

## Migration performance

Writing to the nested set tables is expensive, by design. Expensive writes but
cheap reads.

We recommend disabling it during migration as follows.

```
drush -r app sset entity_hierarchy_disable_writes 1
```

Then run your migration.

Then when you are finished - do all the writes in one.

```
drush -r app sset entity_hierarchy_disable_writes 0
# Rebuild tree for node field named field_parents.
drush entity-hierarchy-rebuild-tree field_parents node
```
