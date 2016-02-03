jQuery(document).ready(function() {
  var timer;

  jQuery("body").on("mouseenter", ".ip-addr", function(){
    var item = jQuery(this);

    timer = setTimeout(function () {
      var url = "http://ip-api.com/json/"+item.text();
      item.after("<div class='ip-add-info'></div>");
     
      jQuery.ajax({
        url:url,
        dataType:"jsonp",
        success:function(returnData){
          var out="";
          
          jQuery.each(returnData, function(i, item){
            var img="";
            
            if(i=="countryCode"){
              img = " <img src='http://www.ip2location.com/images/flags_16/"+item.toLowerCase()+"_16.png'>"
            }
            
            out+=i+": "+item+img+"<br>";
          });
          
          item.next().html(out);
        },
      });
    }, 1000);
  }).on("mouseleave", "td", function(){
    clearTimeout(timer);
    jQuery(".ip-add-info").remove();  
 });
});
 