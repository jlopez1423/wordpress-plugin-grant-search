 jQuery( document ).ready( function($) {

 	$("#org").select2({
 		placeholder: 'All Organizations',
 	});

 	$('body').on('click', '#org-name',function(){
 		processResultsHTML('org-name', '.org-column', $(this));
 	});
 	$('body').on('click', '#grant-amount',function(){
 		processResultsHTML('grant-amount', '.grant-amount-column', $(this));
 	});
 	$('body').on('click', '#grant-period',function(){
 		processResultsHTML('grant-period', '.grant-period-column', $(this));

 	});
 	$('body').on('click', '#approval-date',function(){
 		processResultsHTML('approval-date', '.approval-column',$(this));
 	});


 	//Builds array of results, sorts them and then displays them on screen
 	var processResultsHTML = function(attribute, element_class, current_element){
 		let presortedResults = buildSortingArray(attribute, element_class);
 		presortedResults.sort(dynamicSort("sort_value"));
 		$('.results-table i').remove();
 		if(current_element.hasClass('active')){
 			presortedResults.reverse();
 			$(current_element).removeClass('active');
 			$('<i class="fa fa-angle-down" aria-hidden="true"></i>').appendTo(current_element);
 		}
 		else{
 			$(current_element).addClass('active');
 			$('<i class="fa fa-angle-up" aria-hidden="true"></i>').appendTo(current_element);
 		}
 		outputResults(presortedResults);
 	}.bind(this);

 	//Processes results and builds array of objects containing element itself and sorting value
 	var buildSortingArray = function(sort_by, index){
 		let tempArray = [];
 		$.each( $('.result-wrapper'), function(key, value){
 			let sorter = 0;
 			if( !(index == '.org-column') ){
 				el = $(this).children(index);
 				el = $(el).attr(sort_by).replace(/,/g, "");
 				sorter = parseInt(el);
 			}
 			else{
 				el = $(this).children(index);
 				sorter = $(el).attr(sort_by);
 			}
 			let tempObj = {
 				el: value,
 				sort_value: sorter,
 			};
 		 	tempArray.push(tempObj);
 		});
 		return tempArray;
 	};

 	//Hides olds results and builds new html with sorted results
 	var outputResults = function(array){
 		$('.result-wrapper').remove();
 		array.reverse();
 		array.forEach(function(element){
 			$(element.el).insertAfter('.table-header');
 		});
 	};

 	//Sorts array based on property pased as argument
 	function dynamicSort(property) {
	    var sortOrder = 1;
	    if(property[0] === "-") {
	        sortOrder = -1;
	        property = property.substr(1);
	    }
	    return function (a,b) {
	        var result = (a[property] < b[property]) ? -1 : (a[property] > b[property]) ? 1 : 0;
	        return result * sortOrder;
	    }
	}

 });
