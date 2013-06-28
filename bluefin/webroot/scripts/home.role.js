$("#role-boxes .role-box").hover( 
	function () { 
		$(this).toggleClass("role-highlight"); 
	},  
	function () { 
		$(this).toggleClass("role-highlight"); 
	}
); 

$("#role-boxes .role-box").click(function () { 
	window.location.href = $(this).attr("link");
});

function showModal(dialogSelector, toShow)
{
    $(dialogSelector).modal(toShow ? 'show' : 'hide');
}