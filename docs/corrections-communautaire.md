# Corrections du pack communautaire

`corrections-communautaire.csv` est le seul fichier du pack autorisé à surcharger une
traduction de `community-engineering/language-fr_fr`. Il n'accueille que des **fautes
avérées** (orthographe, grammaire, contresens), jamais une préférence de style.

Chaque correction est aussi proposée en amont. Le vérificateur signale une erreur
`correction-integree` dès que le pack communautaire publie la même traduction : on retire
alors la ligne d'ici.

Les traductions communautaires se proposent sur Crowdin (projet Magento 2, langue
français), qui alimente le dépôt `magento-l10n/language-fr_FR`.

| Clé (module) | Communautaire | Corrigé | Faute | Proposée en amont |
|---|---|---|---|---|
| `Someone logged into this account from another device or browser. Your current session is terminated.` (Magento_Security) | « Quelqu'un d'autre s'est connecté avec ce compte sur un autre appareil ou navigateur. Cette sessions se termine. » | « Quelqu’un s’est connecté à ce compte depuis un autre appareil ou navigateur. Votre session actuelle est terminée. » | accord (« Cette sessions »), « d'autre » redondant | à faire |

## Candidates non retenues (style, pas faute)

- `Edit` et ses composés : le communautaire alterne « Editer » (57 entrées) et « Modifier »
  (61). C'est une question d'harmonisation, pas une faute : à trancher avant d'y toucher.
