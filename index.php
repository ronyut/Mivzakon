<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html lang="he">
<head>
    <title>מבזקון</title>
	<meta charset="utf-8">
    <meta name="keywords" content="">
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
	<style>
    @import url(https://fonts.googleapis.com/earlyaccess/opensanshebrew.css);

    body {
        font-family: 'Open Sans Hebrew', 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif !important;
    }

    .table {
        text-align: right;
        width: 50%;
    }
    
    h1 {
        padding-top: 20px;
    }

    #keywords {
        width: 80%;
        margin-bottom: 20px;
        margin-top: 20px;
    }

    #keywords .badge {
        display: inline-block;
        margin-bottom:5px;
    }

    /* Nice Loader */

    .loader {
        border-top: 16px solid #3498db; /* Blue */
        border-radius: 50%;
        width: 120px;
        height: 120px;
        animation: spin 2s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
	</style>
</head>
<body dir="rtl">
    
    <div align="center">
        <div id="please-wait">
            <h1>אנא המתן... (זה שווה את זה)</h1>
            <hr>
        </div>
        <div id="keywords">
            <div class="loader"></div>
        </div>
    </div>
	<table id="articles" class="table table-hover" align="center">
		<tbody>
			
		</tbody>
	</table>
	<br />
    	
	<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script>
        $.ajax({
            url: "ajax.php",
            type: "GET",
            cache: false,
            success : function(data){
                handleData(data);
            }
        });

        function handleData(data) {
            let html = {keywords: "", articles: ""};

            $.each(data.keywords, function(name, count) {
                if (count >= 10) {
                    html.keywords += `<span class="badge badge-pill badge-primary" style='font-size:`+count+`px'>`+name+`</span> `;
                }
            });
            $("#keywords").html(html.keywords);

            let lastDate;
            $.each(data.articles, function(key, article) {
                if (article.date != lastDate) {
                    html.articles += `<tr><td colspan="3"><h3>`+article.date+`</h3></tr>`;
                    lastDate = article.date;
                }
                html.articles +=
                    `<tr>
                        <td>`+article.source+`</td>
                        <td><b>`+article.hour+`</b></td>
                        <td>`+article.title+`</td>
                    </tr>`;
            });

            $("#articles").html(html.articles);
            $("#please-wait").hide();
        }
	
	</script>
</body>
</html>