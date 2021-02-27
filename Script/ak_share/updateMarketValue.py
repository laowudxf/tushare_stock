import akshare as ak
import pymysql.cursors
import sys
import datetime

args = sys.argv[1:]
isDay = False
if "--day" in args:
    isDay = True

now = datetime.datetime.now()

nowStr = now.strftime('%Y%m%d')
dateStr = "20000101"
if isDay:
    dateStr = nowStr

connection = pymysql.connect(host='127.0.0.1', user='root', password='woailmr', database='tushare', cursorclass=pymysql.cursors.DictCursor)
# connection = pymysql.connect(host='127.0.0.1', user='root', password='woailmr', database='tushare', cursorclass=pymysql.cursors.DictCursor)

def insertStockInfo(stockInfos, stock_id, cursor):
    trunkCount = 5000

    index = 0
    while index < len(stockInfos):
        dates = stockInfos.index.astype(str).str[:10].values
        insertValues = []
        for i in range(trunkCount):
            j = index + i
            if j >= len(stockInfos):
                break
            info = stockInfos.iloc[j]
            outstanding_share = info.get("outstanding_share")
            close = info.get("close")
            marketValue = outstanding_share / 1e8 * close
            date = ''.join(dates[j].split('-'))
            insertValues.append("({}, {}, {})".format(stock_id, date, marketValue))

        a =  ','.join(insertValues)

        sql = "insert into stock_dailies_extra (stock_id, trade_date, market_value) values " + a
        cursor.execute(sql)
        connection.commit()
        index += trunkCount

            # print(date)




with connection:
    with connection.cursor() as cursor:
        sql = "SELECT ts_code, id FROM stocks"
        cursor.execute(sql)
        result = cursor.fetchall()
        for r in result:
            codes = r["ts_code"].split('.')
            stock_id = r["id"]
            print("deal stock_id: " + str(stock_id))
            if codes[1].lower() == "cs":
                continue
            stockInfos = ak.stock_zh_a_daily(symbol=codes[1].lower() + codes[0], start_date=dateStr, adjust="qfq")
            insertStockInfo(stockInfos, stock_id, cursor)
