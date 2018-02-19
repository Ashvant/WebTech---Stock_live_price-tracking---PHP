    <?php
            $AV_URL = '';
            $htmlOutput='';
            $symbol='';
            
            if($_SERVER["REQUEST_METHOD"]=="POST"){
                if(!empty($_POST["symbol"])){
                    $symbol=$_POST["symbol"];
                    $AV_URL = "https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol=".$_POST["symbol"]."&apikey=3VBM8829UR75KK8Y";
                    $news_url = "https://seekingalpha.com/api/sa/combined/".$symbol.".xml";
                    try{
                        $homepage = file_get_contents($AV_URL);
                         $news_xml = file_get_contents($news_url);
                    $response = json_decode($homepage,true);
                    date_default_timezone_set("America/Los_Angeles");
                    reset($response['Time Series (Daily)']);
                    $news_data = simplexml_load_string($news_xml);
                    $keys = array_keys($response['Time Series (Daily)']);
                    $currentDate = $keys[0];
                    $prevDate = $keys[1];
                    $itemsArray = array();
                    $tableArray = array();
                    $timeSeriesPriceArray = array();
                    $timeSeriesVolumeArray = array();
                    $iterator = 0;
                    foreach($response['Time Series (Daily)'] as $key => $value){
                        $originalDate = $key;
                        $newDate = date("m/d", strtotime($originalDate));
                        $timeSeriesPriceArray[$newDate] = (float)$value['4. close'];
                        $timeSeriesVolumeArray[$newDate] =((int)$value['5. volume'])/1000000;
                        $iterator++;
                        if($iterator==130){
                            break;
                        }
                    }
                    $tableArray['symbol'] = $symbol;
                    $tableArray['close'] = $response['Time Series (Daily)'][$currentDate]['4. close'];
                    $tableArray['open'] = $response['Time Series (Daily)'][$currentDate]['1. open'];
                    $tableArray['prev_close'] = $response['Time Series (Daily)'][$prevDate]['4. close'];
                    $change = $response['Time Series (Daily)'][$currentDate]['4. close']-$response['Time Series (Daily)'][$prevDate]['4. close'];
                    $percentChange = $change/$response['Time Series (Daily)'][$prevDate]['4. close']*100;
                    $percentChangeValue = round($percentChange,2);
                    $tableArray['change'] = $change;
                    $tableArray['percent'] = $percentChangeValue;
                    $tableArray['range'] = $response['Time Series (Daily)'][$currentDate]['3. low']."-".$response['Time Series (Daily)'][$currentDate]['2. high'];
                    $tableArray['volume'] = number_format($response['Time Series (Daily)'][$currentDate]['5. volume']);
                    $tableArray['timestamp'] = $currentDate;
                    $iterator = 0;
                    foreach($news_data->channel->item as $item){
                        if(strcmp($item->link,"https://seekingalpha.com/symbol/".$symbol."/news?source=feed_symbol_".$symbol."")==0){
                            continue;
                        }else{
                            $itemArray = array();
                            $itemArray['title'] = $item->title;
                            $itemArray['link'] = $item->link;
                            $itemArray['pub_date'] = $item->pubDate;
                            $itemsArray[$iterator] = $itemArray;
                            $iterator++;
                            if($iterator==5){
                                break;
                            }
                        }
                    }
                    $timeSeriesPriceArray = array_reverse($timeSeriesPriceArray);
                    $timeSeriesVolumeArray = array_reverse($timeSeriesVolumeArray);
                    $result_array = array();
                    $result_array['price'] = $tableArray;
                    $result_array['news']=$itemsArray;
                    $result_array['priceArray'] = $timeSeriesPriceArray;
                    $result_array['volumeArray'] = $timeSeriesVolumeArray;
                    echo json_encode($result_array);
                    exit();
                    }
                    catch(Exception $e){
                        echo "Invalid_data";
                        exit();
                    }
                   
                }
            }
        ?>
<html>
    <head>
        <meta charset="utf-8"/>
        <style>
            body {
                position: absolute;
                align-content: center;
                width: 100%;
                height: 100%;
                overflow: visible;
            }
            #formData {
                margin: 0 auto;
                border: 1px solid #c6bfbf;
                width: 447px;
                height: 180px;
                background: #e3e4e5;
            }
            #horiLine{
                margin: 3px;
                color: lightgrey;
            }
            #searchButton{
                left: 192px;
                position: relative;
                top: 10px;
            }
            #clearButton{
                left: 193px;
                position: relative;
                top: 10px;
            }
            #mandatory{
                    position: relative;
                    top: 20px;
                    left: 3px;
            }
            #title{
                margin: 10px;
                text-align: center;
            }
            #stockPriceDiv{
                position: relative;
                top: 50px;
                height: 245px;
                width:100%;
                margin: auto 0;
                font-family: sans-serif;
                
            }
            .priceTable{
                border: 0.5px solid #b3b4b5;
                border-collapse: collapse;
                margin: auto;
                width:57%;
                font-size: 11px;
            }
            .priceTable1{
                border: 0.5px solid #b3b4b5;
                border-collapse: collapse;
                margin: auto;
                width:100%;
                font-size: 13px;
            }
            tr{
                height: 20px;
            }
            .priceTable1 tr{
                height: 30px;
            }
            .astext {
                background:none;
                border:none;
                margin:0;
                padding:0;
            }
            #container{
                position:relative;
                top:40px;
                margin: auto;
                width:57%;
                height:500px;
            }
            button.accordion {
                cursor: pointer;
                padding: 18px;
                width: 100%;
                border: none;
                text-align: center;
                outline: none;
                font-size: 15px;
                transition: 0.4s;
                color:black;
                background-color: white;

            }
            button.accordion:after {
             
                color: #777;
                font-weight: bold;
                float: right;
                margin-left: 5px;
            }

            div.panel {
                padding: 0 18px;
                background-color: white;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.2s ease-out;
            }
            #news_div{
                position: relative;
                top:40px;
                width:57%;
                margin:auto;
            }
        </style>
        <script src="https://code.highcharts.com/highcharts.src.js"></script>
        <script src="https://code.highcharts.com/modules/exporting.js"></script>
        <script type="text/javascript">
            var responseObject;
            var symbol;
            function pad(s) { return (s < 10) ? '0' + s : s; }
            function getValues(){
                var url = "stocknew.php";
                var url_ind = "https://www.alphavantage.co/query?function=Price&symbol=MSFT&interval=weekly&time_period=10&series_type=open&apikey=3VBM8829UR75KK8Y"
                symbol = document.getElementById("textbox").value;
                if(symbol == ""){
                    alert("Please enter a symbol");
                }else{
                    var xmlhttp = new XMLHttpRequest();
                    xmlhttp.open("POST",url,true);
                    xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
                    xmlhttp.onreadystatechange=function(){
                        if(xmlhttp.readyState==4 && xmlhttp.status == 200){
                            var response = xmlhttp.responseText;
                            try{
                            responseObject = JSON.parse(response);
                            console.log(responseObject);
                            var outputHtml;
                            outputHtml = "<table class=\"priceTable\" border=1><col width=\"90\" style=\"background-color:#e3e4e5\"><col width=\"80\"style=\"background-color:#f1f4f7\"><tr><td><b>Stock Ticker Symbol</b></td><td align=\"center\">"+responseObject.price.symbol+"</td></tr>";
                            outputHtml += "<tr><td><b>Close</b></td><td align=\"center\">"+responseObject.price.close+"</td></tr>";
                            outputHtml += "<tr><td><b>Open</b></td><td align=\"center\">"+responseObject.price.open+"</td></tr>";
                            outputHtml += "<tr><td><b>Previous Close</b></td><td align=\"center\">"+responseObject.price.prev_close+"</td></tr>";
                            if(responseObject.price.change>0){
                                outputHtml += "<tr><td><b>Change</b></td><td align=\"center\">"+responseObject.price.change+"<img src=\"http://cs-server.usc.edu:45678/hw/hw6/images/Green_Arrow_Up.png\"width=10px height=10px></img></td></tr>";
                            }else if(responseObject.price.change<0){
                                outputHtml += "<tr><td><b>Change</b></td><td align=\"center\">"+Math.abs(responseObject.price.change)+"<img src=\"http://cs-server.usc.edu:45678/hw/hw6/images/Red_Arrow_Down.png\"width=10px height=10px></img></td></tr>";
                            }else{
                                outputHtml += "<tr><td><b>Change</b></td><td align=\"center\">"+responseObject.price.change+"</td></tr>";
                            }
                            if(responseObject.price.percent>0){
                                outputHtml += "<tr><td><b>Change</b></td><td align=\"center\">"+responseObject.price.percent+"%<img src=\"http://cs-server.usc.edu:45678/hw/hw6/images/Green_Arrow_Up.png\"width=10px height=10px></img></td></tr>";
                            }else if(responseObject.price.percent<0){
                                outputHtml += "<tr><td><b>Change</b></td><td align=\"center\">"+Math.abs(responseObject.price.percent)+"%<img src=\"http://cs-server.usc.edu:45678/hw/hw6/images/Red_Arrow_Down.png\"width=10px height=10px></img></td></tr>";
                            }else{
                                outputHtml += "<tr><td><b>Change</b></td><td align=\"center\">"+responseObject.price.percent+"%</td></tr>";
                            }
                            outputHtml += "<tr><td><b>Day's Range</b></td><td align=\"center\">"+responseObject.price.range+"</td></tr>";
                            outputHtml += "<tr><td><b>Volume</b></td><td align=\"center\">"+responseObject.price.volume+"</td></tr>";
                            outputHtml += "<tr><td><b>Timestamp</b></td><td align=\"center\">"+responseObject.price.timestamp+"</td></tr>";
                            outputHtml+= "<tr><td><b>Indicators</b></td><td align=\"center\"><a id = \"price\" href=\"javascript:show_price()\" >Price</a>  &nbsp&nbsp<a id = \"sma\" href=\"javascript:show_sma()\" >SMA</a>&nbsp&nbsp<a id = \"ema\" href=\"javascript:show_ema()\" >EMA</a>&nbsp&nbsp<a id = \"stoch\" href=\"javascript:show_stoch()\" >STOCH</a>&nbsp&nbsp<a id = \"rsi\" href=\"javascript:show_rsi()\" >RSI</a>&nbsp&nbsp<a id = \"adx\" href=\"javascript:show_adx()\" >ADX</a>&nbsp&nbsp<a id = \"cci\" href=\"javascript:show_cci()\" >CCI</a>&nbsp&nbsp<a id = \"bbands\" href=\"javascript:show_bbands()\" >BBANDS</a>&nbsp&nbsp<a id = \"macd\" href=\"javascript:show_macd()\" >MACD</a></td></tr>";
                            outputHtml += "</table>";
                            newsHtml = "<button id =\"accord\" class=\"accordion\">Click to show stock news</br><img src=\"http://cs-server.usc.edu:45678/hw/hw6/images/Gray_Arrow_Down.png\" height=20px width=25px></img></button>";
                            
                            newsHtml += "<div class=\"panel\"><table class=\"priceTable1\"border=1 style=\"background-color:#f1f4f7\">";
                            for(var i=0;i<responseObject.news.length;i++){
                                newsHtml += "<tr><td><a target = \"_blank\" href = \""+responseObject.news[i].link[0]+"\">"+responseObject.news[i].title[0]+"</a>&nbsp&nbsp&nbsp&nbsp"+responseObject.news[0].pub_date[0].substring(0,responseObject.news[0].pub_date[0].length-6)+"</td></tr>"
                            }
                            newsHtml += "</table></div>";
                            document.getElementById("stockPriceDiv").innerHTML=outputHtml;
                            document.getElementById("news_div").innerHTML=newsHtml;
                            show_price();
                            document.getElementById("accord").onclick=function(){
                                this.classList.toggle("active");
                                var panel = this.nextElementSibling;
                                var html;
                                if (panel.style.maxHeight){
                                  panel.style.maxHeight = null;
                                  document.getElementById("accord").innerHTML="Click to show stock news</br><img src=\"http://cs-server.usc.edu:45678/hw/hw6/images/Gray_Arrow_Down.png\" height=20px width=25px></img>";
                                } else {
                                  panel.style.maxHeight = panel.scrollHeight + "px";
                                  html = "Click to hide stock news</br><img src=\"http://cs-server.usc.edu:45678/hw/hw6/images/Gray_Arrow_Up.png\" height=20px width=25px></img>";
                                  document.getElementById("accord").innerHTML=html;
                                } 
                            }
                            }
                            catch(err){
                            document.getElementById("clear_div").innerHTML="<div id=\"clear_div\"><div id = \"stockPriceDiv\"></div><div id=\"container\"></div><div id=\"news_div\"></div>";
                            outputHtml = "<table class=\"priceTable\" border=1><col width=\"90\" style=\"background-color:#e3e4e5\"><col width=\"80\"style=\"background-color:#f1f4f7\"><tr><td><b>Error</b></td><td align=\"center\">ERROR: No Record has been found, please enter a valid symbol</td></tr>";
                            outputHtml += "</table>";
                            document.getElementById("stockPriceDiv").innerHTML=outputHtml;
                            }
                   
                        }
                        
                    }
                    xmlhttp.send("symbol="+symbol);
                            
                    
            }
            
        }
            function show_price(){
                var date = new Date();
                var arrayOfPrice = Object.values(responseObject.priceArray).map(parseFloat);
                var arrayOfNumbers = Object.values(responseObject.volumeArray).map(Number);
                Highcharts.chart('container', {
                                chart: {
                                    marginRight:220,
                                    borderWidth:1,
                                    borderColor:'#b3b4b5',
                                    zoomType: 'xy',
                                    width:820
                                },
                                title: {
                                    text: 'Stock Price ('+pad(date.getUTCMonth()+1)+'/'+pad(date.getDate())+'/'+date.getFullYear()+')',
                                    useHTML:true
                                },
                                subtitle: {
                                    text:'<a href=" https://www.alphavantage.co/">Source: Alpha Vantage</a>',
                                    useHTML:true
                                },
                                xAxis: [{
                                    type: 'datetime',
                                    pointStart: Object.keys(responseObject.priceArray)[0],
                                  //  pointInterval: 24 * 3600 * 1000*7, // one day
                                    tickInterval:7,
                                    categories: Object.keys(responseObject.priceArray),
                                    crosshair: true
                                }],
                                yAxis: [{ // Primary yAxis
                                    labels: {
                                        format: '{value}',
                                        style: {
                                            color: Highcharts.getOptions().colors[1]
                                        }
                                    },
                                    title: {
                                        text: 'Stock Price',
                                        style: {
                                            color: Highcharts.getOptions().colors[1]
                                        }
                                    },
                                    pointStart: Object.values(responseObject.priceArray)[0]-50
                                }, { // Secondary yAxis
                                    tickInterval:25,
                                    title: {
                                        text: 'Volume',
                                        style: {
                                            color: Highcharts.getOptions().colors[1]
                                        }
                                    },
                                    labels: {
                                        format: '{value} mn',
                                        style: {
                                            color:  Highcharts.getOptions().colors[1]
                                        }
                                    },
                                    opposite: true
                                }],
                                tooltip: {
                                    shared: false
                                },
                                legend: {
                                    layout: 'vertical',
                                    align: 'left',
                                    x: 680,
                                    verticalAlign: 'top',
                                    y: 200,
                                    floating: true,
                                    backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'
                                },
                                plotOptions: {
                                    area: {
                                        lineColor: '#e50202',
                                        lineWidth: 1,
                                        marker: {
                                            enabled: false,
                                            symbol: 'circle',
                                            radius: 2,
                                            states: {
                                                hover: {
                                                    enabled: true

                                                }
                                            }
                                        },
                                        threshold: null
                                    }
                                },
                                series: [{
                                    name: responseObject.price.symbol,
                                    type: 'area',
                                    data: arrayOfPrice,
                                    tooltip: {
                                        valueSuffix: ''
                                    },
                                    color:'#ef6e6e',
                                    },{
                                    name: responseObject.price.symbol+' Volume',
                                    type: 'column',
                                    yAxis: 1,
                                    data: arrayOfNumbers,
                                    tooltip: {
                                        valueSuffix: ' mn'
                                    },
                                    color:'#FFFFFF'  
                                }]
                            });
                            return;
                        }
            function show_sma(){
                var xmlhttp = new XMLHttpRequest();
                var url = "https://www.alphavantage.co/query?function=SMA&symbol="+symbol+"&interval=daily&time_period=10&series_type=open&apikey=3VBM8829UR75KK8Y";
                    xmlhttp.open("GET",url,true);
                    xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
                    xmlhttp.onreadystatechange=function(){
                        if(xmlhttp.readyState==4 && xmlhttp.status == 200){
                            var response = xmlhttp.responseText;
                            var responseJSON = JSON.parse(response);
                            console.log(responseJSON); 
                            var dateArray=[];
                            var valueArray=[];
                            var j=0;
                            for(var i=130;i>=0;i--){ 
                                var date = new Date(Object.keys(responseJSON["Technical Analysis: SMA"])[i]);
                                dateArray[j] = pad(date.getUTCMonth()+1)+"/"+pad((date.getDate()+1)%31);
                                valueArray[j] = parseFloat(Object.values(responseJSON["Technical Analysis: SMA"])[i].SMA);
                                j++;
                                
                            }
                            Highcharts.chart('container', {
                                chart: {
                                    marginRight:100,
                                    borderWidth:1,
                                    borderColor:'#b3b4b5',
                                    zoomType: 'xy',
                                    width:820
                                },
                                title: {
                                    text: 'Simple Moving Average (SMA)',
                                    useHTML:true
                                },
                                subtitle: {
                                    text:'<a href=" https://www.alphavantage.co/">Source: Alpha Vantage</a>',
                                    useHTML:true
                                },
                                xAxis: [{
                                    type: 'datetime',
                                    tickInterval:7,
                                    pointStart: dateArray[0],
                                    categories: dateArray,
                                    crosshair: true
                                }],
                                yAxis: [{ // Primary yAxis
                                    labels: {
                                        format: '{value}',
                                        style: {
                                            color: Highcharts.getOptions().colors[1]
                                        }
                                    },
                                    title: {
                                        text: 'SMA',
                                        style: {
                                            color: Highcharts.getOptions().colors[1]
                                        }
                                    }
                                }],
                                legend: {
                                    layout: 'vertical',
                                    align: 'left',
                                    x: 730,
                                    verticalAlign: 'top',
                                    y: 200,
                                    floating: true,
                                    backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'
                                },
                                plotOptions: {
                                    line: {
                                        lineColor: '#e50202',
                                        lineWidth: 1,
                                        marker: {
                                            enabled: true,
                                            symbol: 'circle',
                                            radius: 2,
                                            states: {
                                                hover: {
                                                    enabled: true

                                                }
                                            }
                                        },
                                        threshold: null
                                    }
                                },
                                series: [{
                                    name: responseObject.price.symbol,
                                    type: 'line',
                                    data: valueArray,
                                    tooltip: {
                                        valueSuffix: ''
                                    },
                                    color:'#ef6e6e',
                                    }]
                            });
                        }
                    }
                xmlhttp.send();    
            }
            function show_ema(){
                var xmlhttp = new XMLHttpRequest();
                var url = "https://www.alphavantage.co/query?function=EMA&symbol="+symbol+"&interval=weekly&time_period=10&series_type=open&apikey=3VBM8829UR75KK8Y";
                    xmlhttp.open("GET",url,true);
                    xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
                    xmlhttp.onreadystatechange=function(){
                        if(xmlhttp.readyState==4 && xmlhttp.status == 200){
                            var response = xmlhttp.responseText;
                            var responseJSON = JSON.parse(response);
                            console.log(responseJSON);
                            var dateArray=[];
                            var valueArray=[];
                            var j=0;
                            for(var i=130;i>=0;i--){ 
                                var date = new Date(Object.keys(responseJSON["Technical Analysis: EMA"])[i]);
                                dateArray[j] = pad(date.getUTCMonth()+1)+"/"+pad((date.getDate()+1)%31);
                                valueArray[j] = parseFloat(Object.values(responseJSON["Technical Analysis: EMA"])[i].EMA);
                                j++;
                                
                            }
                            Highcharts.chart('container', {
                                chart: {
                                    marginRight:100,
                                    borderWidth:1,
                                    borderColor:'#b3b4b5',
                                    zoomType: 'xy',
                                    width:820
                                },
                                title: {
                                    text: 'Exponential Moving Average (EMA)',
                                    useHTML:true
                                },
                                subtitle: {
                                    text:'<a href=" https://www.alphavantage.co/">Source: Alpha Vantage</a>',
                                    useHTML:true
                                },
                                xAxis: [{
                                    type: 'datetime',
                                    tickInterval:7,
                                    pointStart: dateArray[0],
                                    categories: dateArray,
                                    crosshair: true
                                }],
                                yAxis: [{ // Primary yAxis
                                    labels: {
                                        format: '{value}',
                                        style: {
                                            color: Highcharts.getOptions().colors[1]
                                        }
                                    },
                                    title: {
                                        text: 'EMA',
                                        style: {
                                            color: Highcharts.getOptions().colors[1]
                                        }
                                    }
                                }],
                                legend: {
                                    layout: 'vertical',
                                    align: 'left',
                                    x: 730,
                                    verticalAlign: 'top',
                                    y: 200,
                                    floating: true,
                                    backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'
                                },
                                plotOptions: {
                                    line: {
                                        lineColor: '#e50202',
                                        lineWidth: 1,
                                        marker: {
                                            enabled: true,
                                            symbol: 'circle',
                                            radius: 2,
                                            states: {
                                                hover: {
                                                    enabled: true

                                                }
                                            }
                                        },
                                        threshold: null
                                    }
                                },
                                series: [{
                                    name: responseObject.price.symbol,
                                    type: 'line',
                                    data: valueArray,
                                    tooltip: {
                                        valueSuffix: ''
                                    },
                                    color:'#ef6e6e',
                                    }]
                            });
                       }
                    }
                xmlhttp.send();    
            }
            function show_stoch(){
                var xmlhttp = new XMLHttpRequest();
                var url = "https://www.alphavantage.co/query?function=STOCH&symbol="+symbol+"&interval=daily&time_period=10&series_type=open&apikey=3VBM8829UR75KK8Y";
                    xmlhttp.open("GET",url,true);
                    xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
                    xmlhttp.onreadystatechange=function(){
                        if(xmlhttp.readyState==4 && xmlhttp.status == 200){
                            var response = xmlhttp.responseText;
                            var responseJSON = JSON.parse(response);
                            console.log(responseObject);
                            var dateArray=[];
                            var valueArray=[];
                            var valueArray2 = [];
                            var j=0;
                            for(var i=130;i>=0;i--){ 
                                var date = new Date(Object.keys(responseJSON["Technical Analysis: STOCH"])[i]);
                                dateArray[j] = pad(date.getUTCMonth()+1)+"/"+pad((date.getDate()+1)%31);
                                valueArray[j] = parseFloat(Object.values(responseJSON["Technical Analysis: STOCH"])[i].SlowD);
                                valueArray2[j] = parseFloat(Object.values(responseJSON["Technical Analysis: STOCH"])[i].SlowK);
                                j++;
                                
                            }
                            Highcharts.chart('container', {
                                chart: {
                                    marginRight:100,
                                    borderWidth:1,
                                    borderColor:'#b3b4b5',
                                    zoomType: 'xy',
                                    width:820
                                },
                                title: {
                                    text: 'Stochastic Oscillator (STOCH)',
                                    useHTML:true
                                },
                                subtitle: {
                                    text:'<a href=" https://www.alphavantage.co/">Source: Alpha Vantage</a>',
                                    useHTML:true
                                },
                                xAxis: [{
                                    type: 'datetime',
                                    pointStart: dateArray[0],
                                    tickInterval:7,
                                    categories: dateArray,
                                    crosshair: true
                                }],
                                yAxis: [{ // Primary yAxis
                                    labels: {
                                        format: '{value}',
                                        style: {
                                            color: Highcharts.getOptions().colors[1]
                                        }
                                    },
                                    title: {
                                        text: 'STOCH',
                                        style: {
                                            color: Highcharts.getOptions().colors[1]
                                        }
                                    }
                                }],
                                legend: {
                                    layout: 'vertical',
                                    align: 'left',
                                    x: 695,
                                    verticalAlign: 'top',
                                    y: 200,
                                    floating: true,
                                    backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'
                                },
                                plotOptions: {
                                    line: {
                                    
                                        lineWidth: 1,
                                        marker: {
                                            enabled: true,
                                            symbol: 'circle',
                                            radius: 2,
                                            states: {
                                                hover: {
                                                    enabled: true

                                                }
                                            }
                                        },
                                        threshold: null
                                    }
                                },
                                series: [{
                                    name: responseObject.price.symbol+" SlowK",
                                    type: 'line',
                                    data: valueArray2,
                                    tooltip: {
                                        valueSuffix: ''
                                    },
                                    color:'#ef6e6e',
                                    },{
                                    name: responseObject.price.symbol+" SlowD",
                                    type: 'line',
                                    data: valueArray,
                                    tooltip: {
                                        valueSuffix: ''
                                    },
                                    color:'#5990ea',
                                    }]
                            });
                        }
                    }
                xmlhttp.send();    
            }
            function show_rsi(){
                var xmlhttp = new XMLHttpRequest();
                var url = "https://www.alphavantage.co/query?function=RSI&symbol="+symbol+"&interval=daily&time_period=10&series_type=open&apikey=3VBM8829UR75KK8Y";
                    xmlhttp.open("GET",url,true);
                    xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
                    xmlhttp.onreadystatechange=function(){
                        if(xmlhttp.readyState==4 && xmlhttp.status == 200){
                            var response = xmlhttp.responseText;
                            var responseJSON = JSON.parse(response);
                            console.log(responseJSON);
                            var dateArray=[];
                            var valueArray=[];
                            var j=0;
                            for(var i=130;i>=0;i--){ 
                                var date = new Date(Object.keys(responseJSON["Technical Analysis: RSI"])[i]);
                                dateArray[j] = pad(date.getUTCMonth()+1)+"/"+pad((date.getDate()+1)%31);
                                valueArray[j] = parseFloat(Object.values(responseJSON["Technical Analysis: RSI"])[i].RSI);
                                j++;
                                
                            }
                            Highcharts.chart('container', {
                                chart: {
                                    marginRight:100,
                                    borderWidth:1,
                                    borderColor:'#b3b4b5',
                                    zoomType: 'xy',
                                    width:820
                                },
                                title: {
                                    text: 'Relative Strength Index (RSI)',
                                    useHTML:true
                                },
                                subtitle: {
                                    text:'<a href=" https://www.alphavantage.co/">Source: Alpha Vantage</a>',
                                    useHTML:true
                                },
                                xAxis: [{
                                    type: 'datetime',
                                    pointStart: dateArray[0],
                                    tickInterval:7,
                                    categories: dateArray,
                                    crosshair: true
                                }],
                                yAxis: [{ // Primary yAxis
                                    labels: {
                                        format: '{value}',
                                        style: {
                                            color: Highcharts.getOptions().colors[1]
                                        }
                                    },
                                    title: {
                                        text: 'RSI',
                                        style: {
                                            color: Highcharts.getOptions().colors[1]
                                        }
                                    }
                                }],
                                legend: {
                                    layout: 'vertical',
                                    align: 'left',
                                    x: 730,
                                    verticalAlign: 'top',
                                    y: 200,
                                    floating: true,
                                    backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'
                                },
                                plotOptions: {
                                    line: {
                                        lineColor: '#e50202',
                                        lineWidth: 1,
                                        marker: {
                                            enabled: true,
                                            symbol: 'circle',
                                            radius: 2,
                                            states: {
                                                hover: {
                                                    enabled: true

                                                }
                                            }
                                        },
                                        threshold: null
                                    }
                                },
                                series: [{
                                    name: responseObject.price.symbol,
                                    type: 'line',
                                    data: valueArray,
                                    tooltip: {
                                        valueSuffix: ''
                                    },
                                    color:'#ef6e6e',
                                    }]
                            });
                            
                        }
                    }
                xmlhttp.send();    
            }
            function show_adx(){
                var xmlhttp = new XMLHttpRequest();
                var url = "https://www.alphavantage.co/query?function=ADX&symbol="+symbol+"&interval=daily&time_period=10&series_type=open&apikey=3VBM8829UR75KK8Y";
                    xmlhttp.open("GET",url,true);
                    xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
                    xmlhttp.onreadystatechange=function(){
                        if(xmlhttp.readyState==4 && xmlhttp.status == 200){
                            var response = xmlhttp.responseText;
                            var responseJSON = JSON.parse(response);
                            console.log(responseJSON);
                            var dateArray=[];
                            var valueArray=[];
                            var j=0;
                            for(var i=130;i>=0;i--){ 
                                var date = new Date(Object.keys(responseJSON["Technical Analysis: ADX"])[i]);
                                dateArray[j] = pad(date.getUTCMonth()+1)+"/"+pad((date.getDate()+1)%31);
                                valueArray[j] = parseFloat(Object.values(responseJSON["Technical Analysis: ADX"])[i].ADX);
                                j++;
                                
                            }
                            Highcharts.chart('container', {
                                chart: {
                                    marginRight:100,
                                    borderWidth:1,
                                    borderColor:'#b3b4b5',
                                    zoomType: 'xy',
                                    width:820
                                },
                                title: {
                                    text: 'Average Directional Movement Index (ADX)',
                                    useHTML:true
                                },
                                subtitle: {
                                    text:'<a href=" https://www.alphavantage.co/">Source: Alpha Vantage</a>',
                                    useHTML:true
                                },
                                xAxis: [{
                                    type: 'datetime',
                                    tickInterval:7,
                                    pointStart: dateArray[0],
                                    categories: dateArray,
                                    crosshair: true
                                }],
                                yAxis: [{ // Primary yAxis
                                    labels: {
                                        format: '{value}',
                                        style: {
                                            color: Highcharts.getOptions().colors[1]
                                        }
                                    },
                                    title: {
                                        text: 'ADX',
                                        style: {
                                            color: Highcharts.getOptions().colors[1]
                                        }
                                    }
                                }],
                                legend: {
                                    layout: 'vertical',
                                    align: 'left',
                                    x: 730,
                                    verticalAlign: 'top',
                                    y: 200,
                                    floating: true,
                                    backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'
                                },
                                plotOptions: {
                                    line: {
                                        lineColor: '#e50202',
                                        lineWidth: 1,
                                        marker: {
                                            enabled: true,
                                            symbol: 'circle',
                                            radius: 2,
                                            states: {
                                                hover: {
                                                    enabled: true

                                                }
                                            }
                                        },
                                        threshold: null
                                    }
                                },
                                series: [{
                                    name: responseObject.price.symbol,
                                    type: 'line',
                                    data: valueArray,
                                    tooltip: {
                                        valueSuffix: ''
                                    },
                                    color:'#ef6e6e',
                                    }]
                            });
                            
                        }
                    }
                xmlhttp.send();    
            }
            function show_cci(){
                var xmlhttp = new XMLHttpRequest();
                var url = "https://www.alphavantage.co/query?function=CCI&symbol="+symbol+"&interval=daily&time_period=10&series_type=open&apikey=3VBM8829UR75KK8Y";
                    xmlhttp.open("GET",url,true);
                    xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
                    xmlhttp.onreadystatechange=function(){
                        if(xmlhttp.readyState==4 && xmlhttp.status == 200){
                            var response = xmlhttp.responseText;
                            responseObject = JSON.parse(response);
                            console.log(responseObject);
                        }
                    }
                xmlhttp.send();    
            }
            function show_bbands(){
                var xmlhttp = new XMLHttpRequest();
                var url = "https://www.alphavantage.co/query?function=BBANDS&symbol="+symbol+"&interval=daily&time_period=10&series_type=open&apikey=3VBM8829UR75KK8Y";
                    xmlhttp.open("GET",url,true);
                    xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
                    xmlhttp.onreadystatechange=function(){
                        if(xmlhttp.readyState==4 && xmlhttp.status == 200){
                            var response = xmlhttp.responseText;
                            responseObject = JSON.parse(response);
                            console.log(responseObject);
                        }
                    }
                xmlhttp.send();    
            }
            function show_macd(){
                var xmlhttp = new XMLHttpRequest();
                var url = "https://www.alphavantage.co/query?function=MACD&symbol="+symbol+"&interval=daily&time_period=10&series_type=open&apikey=3VBM8829UR75KK8Y";
                    xmlhttp.open("GET",url,true);
                    xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
                    xmlhttp.onreadystatechange=function(){
                        if(xmlhttp.readyState==4 && xmlhttp.status == 200){
                            var response = xmlhttp.responseText;
                            responseObject = JSON.parse(response);
                            console.log(responseObject);
                        }
                    }
                xmlhttp.send();    
            }
            function clearValues(){
                document.getElementById("textbox").value="";
                document.getElementById("clear_div").innerHTML="<div id=\"clear_div\"><div id = \"stockPriceDiv\"></div><div id=\"container\"></div><div id=\"news_div\"></div>";
            }
        </script>
    </head>
    <body>
    
        <form id = "formData">
            <h2 id=title><b><i>Stock Search</i></b></h2>
            <hr id = "horiLine">
            Enter Stock Ticker Symbol:*<input id ="textbox" type="text" name="symbol" value="<?php echo $symbol;?>"><br>
            <input type="button" value="Search" id="searchButton" onclick="getValues()">
            <input type="button" value="Clear" id="clearButton" onClick="clearValues()" ><br>
            <text id="mandatory"><i>*-Mandatory fields.</i></text>
        </form>
        <div id="clear_div">
        <div id = "stockPriceDiv"></div>
        <div id="container"></div>
        <div id="news_div"></div>
        </div>
    </body>
</html>