			<div style="clear:both;padding:10px"></div>
			</div>
			</div>
		</div>
		<script>
			var links = document.querySelectorAll('a');
			for (var i = 0; i < links.length; i++) {
			  if (links[i].getAttribute('href').match(/delete/g)){
				  links[i].addEventListener('click', function(event) {
					  event.preventDefault();
					  var choice = confirm('Are you sure you want to delete this item?');
					  if (choice) {
						window.location.href = this.getAttribute('href');
					  }
				  });
			  }
			}
		</script>
	</body>
</html>