window.onscroll = function() {scrollFunction()};

function scrollFunction() {

  if (document.body.scrollTop > 0 || document.documentElement.scrollTop > 0) {
    document.getElementById("wrapper").style.top = "-150px";
  } else {
    document.getElementById("wrapper").style.top = "0";
  }

}