window.onscroll = function() {scrollFunction()};

function scrollFunction() {

  if (document.body.scrollTop > 1170 || document.documentElement.scrollTop > 1170) {
    document.getElementById("wrapper").style.top = "-150px";
  } else {
    document.getElementById("wrapper").style.top = "0";
  }

}