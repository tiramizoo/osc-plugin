$(document).ready(function(){

	if (!window.tiramizoo_init) {

		window.tiramizoo_init = true;

		if ($('#tiramizoo-title').length === 1) {
		
			var $d = {};
			var $i = {};
			var $h = [];
		
			/* shipping list; hide all the stuff */
		
			$('#tiramizoo-title').parent().parent().parent().after('<tr id="tiramizoo-row"><td id="tiramizoo-action"><div id="tiramizoo-container"><div id="tiramizoo-action-label"></div> Lieferung am <br /><div id="tiramizoo-action-select-date"></div> <div id="tiramizoo-action-select-time"></div></div></td><td id="tiramizoo-price"></td><td align="right"><input type="radio" value="on" id="tiramizoo-enabled-on" name="tiramizoo-enabled" /><input type="radio" value="off" id="tiramizoo-enabled-off" name="tiramizoo-enabled" style="display: none;" /></td></tr>');
			$('.tiramizoo-element').parent().parent().hide();
		
			$('.tiramizoo-element').each(function(idx,e){
		
				$data = JSON.parse($(e).attr('data-tiramizoo'));
				
				if (!($data.datehash in $d)) {
								
					$d[($data.datehash)] = [];
					$h.push($data.datehash);
				
				}
			
				$i[($data.idhash)] = $data;
				$d[($data.datehash)].push($data);									
			
			});
					
			// date and then time dropdown
		
			$('#tiramizoo-action-select-date').html('<select size="1" name="tiramizoo-select-date" id="tiramizoo-select-date"></select>');

			$($h).each(function(idx,e){
					
				$e = $d[e];
					
				$('#tiramizoo-action-select-time').append('<select size="1" name="tiramizoo-select-time" class="tiramizoo-select-time" id="tiramizoo-select-time-'+($d[e][0].datehash)+'"></select>');
									
				$('#tiramizoo-select-date').append('<option value="'+($d[e][0].datehash)+'">'+($d[e][0].date)+'</option>');
			
				$($d[e]).each(function(idx,$t) {
									
					$('#tiramizoo-select-time-'+($t.datehash)).append('<option value="'+($t.idhash)+'" class="tiramizoo-time tiramizoo-time-'+($t.datehash)+'">'+($t.after)+' - '+($t.before)+' für '+($t.price)+'</option>');					
				
				});				
			
			});

			var changer = function(){
				setTimeout(function(){
					$val = $('#tiramizoo-select-date').val();
					$tval = $('#tiramizoo-select-time-'+$val).val();
					$('#tiramizoo-price').html($i[$tval]["price"])
					$('input[name=shipping]').each(function(idx,e){
						if ($(e).val() == $i[$tval]["id"]) {
							$(e).click();
						}
					});
				},100);
			}

			$('select.tiramizoo-select-time').change(function(){ changer(); });
		
			changer();
			
			var selecter = function(){				
				$val = $('#tiramizoo-select-date').val();
				$('select.tiramizoo-select-time').hide();
				$('select#tiramizoo-select-time-'+$val).show();
				$('select#tiramizoo-select-time-'+$val).val($('#tiramizoo-select-time-'+$val+' option:visible').eq(0).attr('value'));
				changer();
			}
			
			selecter();			
			
			$('#tiramizoo-select-date').change(function(){ selecter(); });
				
			/* check if enabled */
							
			var trigger = function() {

				setTimeout(function(){

					if ($('input[name=shipping]:checked').val().match(/^tiramizoo/)) {

						$('#tiramizoo-enabled-off').removeAttr('checked');
						$('#tiramizoo-enabled-on').attr('checked','checked');

					} else {
				
						$('#tiramizoo-enabled-on').removeAttr('checked');
						$('#tiramizoo-enabled-off').attr('checked','checked');

					}
				
				},10);
				
			}
			
			$('#tiramizoo-enabled-on').change(function(){
			
				$val = $('#tiramizoo-select-date').val();
				$tval = $('#tiramizoo-select-time-'+$val).val();
				$('input[name=shipping]').each(function(idx,e){
					if ($(e).val() == $i[$tval]["id"]) {
						$(e).click();
					}
				});
				
			});
			
			$('.moduleRow,.moduleRowSelected').click(function(){
			
				trigger();
				
			});
			
			
			$('input[name=shipping]').click(function(){
			
				trigger();
				
			});	
			
			trigger();	
			
			/* content */
			
			$('#tiramizoo-action-label').html('Schnelle und zuverlässige Lieferung bis zur Haustür durch professionelle Kuriere. Lass es Dir liefern - sofort!');
		
			/* style */

			$('#tiramizoo-row').css({
				backgroundColor: '#85BF05',
				border: '1px solid #85BF05'
			});

			$('#tiramizoo-row td').css({
				backgroundColor: '#eee'
			});

			$('#tiramizoo-container').css({
				display: 'block',
				padding: '50px 10px 20px',
				background: '#fff url(images/shipping-tiramizoo.png) 10px 20px no-repeat'
			});
			
			$('#tiramizoo-container div').css({
				display: 'inline'
			});

			$('#tiramizoo-action-label').css({
				display: 'block',
				marginBottom: '20px'
			});
			
						
		}
	
	}
	
});