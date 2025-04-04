# ezt: Easy Translation Demo

This repository demonstrates how to add entity translation functionality to Easyadmin administrative backend using knplabs/doctrine-behaviors and a2lix/translation-form-bundle.

It may someday also serve as a playground for json-translate.

This repository is based on the tutorial described at [https://dzhebrak.com/blog/translating-entities-easyadmin-doctrinebehaviors](https://dzhebrak.com/blog/translating-entities-easyadmin-doctrinebehaviors?utm_source=github.com&utm_medium=tutorial_demo&utm_campaign=easyadmin_entity_translation&utm_id=opensource) and is a fork of ..


# Database

Uses https://github.com/tacman/DoctrineBehaviors 

https://dzhebrak.com/blog/translating-entities-easyadmin-doctrinebehaviors?utm_source=github.com&utm_medium=tutorial_demo&utm_campaign=easyadmin_entity_translation&utm_id=opensource



# Data

## European Parliament.
Language pairs from the European Parliament. Not globally aligned (files are paired by source/target), but could be interesting to find common phrases.  1.5G compressed


wget https://www.statmt.org/europarl/v7/europarl.tgz -O data/europarl


More details at https://www.statmt.org/europarl/

## 


https://www.kaggle.com/datasets/hgultekin/paralel-translation-corpus-in-22-languages?resource=download

pd.read_csv(CD+SL+'-'+TL+'/'+SL+'-'+TL+'.txt', sep='\t', header = None)[[0,1]].rename(columns = {0:SL, 1:TL})

An "Article" entity will be created with the ability to translate title, slug and content into multiple languages (English and French - mandatory, German - optional), as well as the ability to filter by translated fields.

