#!/bin/bash
./equal.run --get=model_export-all-json --entity=core\\Group > core_Group.json
./equal.run --get=model_export-all-json --entity=core\\Setting > core_Setting.json
./equal.run --get=model_export-all-json --entity=core\\SettingValue > core_SettingValue.json
./equal.run --get=model_export-all-json --entity=finance\\accounting\\AccountChart > finance_accounting_AccountChart.json
./equal.run --get=model_export-all-json --entity=finance\\accounting\\AccountChartLine > finance_accounting_AccountChartLine.json
./equal.run --get=model_export-all-json --entity=finance\\accounting\\AccountingRuleLine > finance_accounting_AccountingRuleLine.json
./equal.run --get=model_export-all-json --entity=finance\\accounting\\AnalyticChart > finance_accounting_AnalyticChart.json
./equal.run --get=model_export-all-json --entity=finance\\accounting\\AnalyticSection > finance_accounting_AnalyticSection.json
./equal.run --get=model_export-all-json --entity=finance\\stats\\StatSection > finance_stats_StatSection.json
./equal.run --get=model_export-all-json --entity=finance\\tax\\VatRule > finance_tax_VatRule.json
./equal.run --get=model_export-all-json --entity=identity\\Address > identity_Address.json
./equal.run --get=model_export-all-json --entity=identity\\Identity > identity_Identity.json
./equal.run --get=model_export-all-json --entity=identity\\Partner > identity_Partner.json
./equal.run --get=model_export-all-json --entity=lodging\\finance\\accounting\\AccountingRule > lodging_finance_accounting_AccountingRule.json
./equal.run --get=model_export-all-json --entity=lodging\\identity\\Center > lodging_identity_Center.json
./equal.run --get=model_export-all-json --entity=lodging\\identity\\CenterCategory > lodging_identity_CenterCategory.json      
./equal.run --get=model_export-all-json --entity=lodging\\identity\\User > lodging_identity_User.json
./equal.run --get=model_export-all-json --entity=lodging\\realestate\\RentalUnit > lodging_realestate_RentalUnit.json
./equal.run --get=model_export-all-json --entity=lodging\\sale\\catalog\\Group > lodging_sale_catalog_Group.json
./equal.run --get=model_export-all-json --entity=lodging\\sale\\catalog\\Product > lodging_sale_catalog_Product.json
./equal.run --get=model_export-all-json --entity=lodging\\sale\\catalog\\ProductModel > lodging_sale_catalog_ProductModel.json 
./equal.run --get=model_export-all-json --entity=realestate\\RentalUnitCategory > realestate_RentalUnitCategory.json
./equal.run --get=model_export-all-json --entity=sale\\autosale\\AutosaleList > sale_autosale_AutosaleList.json
./equal.run --get=model_export-all-json --entity=sale\\autosale\\AutosaleListCategory > sale_autosale_AutosaleListCategory.json
./equal.run --get=model_export-all-json --entity=sale\\booking\\BookingType > sale_booking_BookingType.json
./equal.run --get=model_export-all-json --entity=sale\\catalog\\Category > sale_catalog_Category.json
./equal.run --get=model_export-all-json --entity=sale\\catalog\\Family > sale_catalog_Family.json
./equal.run --get=model_export-all-json --entity=sale\\catalog\\Option > sale_catalog_Option.json
./equal.run --get=model_export-all-json --entity=sale\\catalog\\OptionValue > sale_catalog_OptionValue.json
./equal.run --get=model_export-all-json --entity=sale\\catalog\\PackLine > sale_catalog_PackLine.json
./equal.run --get=model_export-all-json --entity=sale\\catalog\\ProductAttribute > sale_catalog_ProductAttribute.json
./equal.run --get=model_export-all-json --entity=sale\\customer\\Customer > sale_customer_Customer.json
./equal.run --get=model_export-all-json --entity=sale\\customer\\CustomerNature > sale_customer_CustomerNature.json
./equal.run --get=model_export-all-json --entity=sale\\customer\\CustomerType > sale_customer_CustomerType.json
./equal.run --get=model_export-all-json --entity=sale\\customer\\RateClass > sale_customer_RateClass.json
./equal.run --get=model_export-all-json --entity=sale\\discount\\Condition > sale_discount_Condition.json
./equal.run --get=model_export-all-json --entity=sale\\discount\\Discount > sale_discount_Discount.json
./equal.run --get=model_export-all-json --entity=sale\\discount\\DiscountCategory > sale_discount_DiscountCategory.json        
./equal.run --get=model_export-all-json --entity=sale\\discount\\DiscountList > sale_discount_DiscountList.json
./equal.run --get=model_export-all-json --entity=sale\\discount\\DiscountListCategory > sale_discount_DiscountListCategory.json
./equal.run --get=model_export-all-json --entity=sale\\price\\Price > sale_price_Price.json
./equal.run --get=model_export-all-json --entity=sale\\price\\PriceList > sale_price_PriceList.json
./equal.run --get=model_export-all-json --entity=sale\\price\\PriceListCategory > sale_price_PriceListCategory.json
./equal.run --get=model_export-all-json --entity=sale\\season\\Season > sale_season_Season.json
./equal.run --get=model_export-all-json --entity=sale\\season\\SeasonCategory > sale_season_SeasonCategory.json
./equal.run --get=model_export-all-json --entity=sale\\season\\SeasonPeriod > sale_season_SeasonPeriod.json
./equal.run --get=model_export-all-json --entity=sale\\season\\SeasonType > sale_season_SeasonType.json
