## A plugin for [Shopware 6](https://github.com/shopware/platform)

Automatically fetches the currency exchange rates via https://apilayer.com/marketplace/exchangerates_data-api. 

In order to use this plugin you need to request a free API key (250 calls per month, so maximum of 8 times a day).

After you installed the plugin you need to configure your default currency, and the currencies that you want to have automized.

You also need the following command in your crontab:
```bin/console melv:fetch-currency-factor```

*Config:* 
![](https://i.imgur.com/Zbr3LAd.png)

*Command:*
![](https://i.imgur.com/mjWJmla.png)

## Requirements

| Version 	| Requirements               	|
|---------	|----------------------------	|
| 1.0.0    	| Shopware 6.4 >=	            |

## License

Plugin's Icon by [flaticon](https://www.flaticon.com).

The plugin is released under MIT. For a full overview check the [LICENSE](./LICENSE) file.
