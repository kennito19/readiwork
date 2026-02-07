//alert(Math.floor(1000 + Math.random() * 9000));
$(document).ready(function() {
  var pre_email = new Array("Mutua", "Kip", "Wesonga","Oloo","Mwanaisha","Fred");
  var address = new Array("5,000","12,000","20,000","7,000","12,989");

  var pre_phone = new Array("0720","0719","011","0756","0718","0722","0719","0727","0725");

  //var suf_phone = Math.floor(1000 + Math.random() * 9000);

  var full_phone = pre_phone[Math.floor(Math.random() * pre_phone.length)] + "xxx" + Math.floor(1000 + Math.random() * 9000);

  var course = new Array("Car Loan","Emergency Loan","Educational Loan", "Rental Loan");
  var i=0;
  function call_social_proof(){
  i++;
      //alert("recall"+i);
    $(".object").html(pre_phone[Math.floor(Math.random() * pre_phone.length)] + "xxx" + Math.floor(1000 + Math.random() * 9000));
    $(".address").html(address[Math.floor(Math.random() * address.length)]);
    $(".name").html(pre_email[Math.floor(Math.random() * pre_email.length)]);
    $(".course").html(course[Math.floor(Math.random() * course.length)]);
    $(".time-counter").html(Math.floor(10 + Math.random() * 50));

    $("#social-proof").animate({bottom:"10px"},1000,"easeInOutBack",function(){
      $(this).delay(5000).animate({bottom:"-110px"},500,"easeInQuart",function(){
        setTimeout(function(){call_social_proof()}, Math.floor(3000 + Math.random() * 15000));
      });

    });

      //call_social_proof();
  }
  call_social_proof();
});
