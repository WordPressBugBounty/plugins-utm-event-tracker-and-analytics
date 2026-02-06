<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly 
}

?>

<div class="utm-event-tracker-pagination-container">
	<ul class="utm-event-tracker-pagination" v-if="get_total_pages > 1">
		<li :disabled="!(currentPage > 1)" class="first-page"><a @click.prevent="go_page(1)" href="#">First Page</a></li>
		<li :disabled="!(currentPage > 1)" class="prev-page"><a @click.prevent="go_page(currentPage - 1)" href="#">Previous Page</a></li>

		<li v-for="page in get_pages" :class="{active: page == currentPage}"><a @click.prevent="go_page(page)" href="#">{{page}}</a></li>

		<li :disabled="!has_last_page" class="next-page"><a @click.prevent="go_page(currentPage + 1)" href="#">Next Page</a></li>
		<li :disabled="!has_last_page" class="last-page"><a @click.prevent="go_page(get_last_page_number)" href="#">Last Page</a></li>
	</ul>

	<select v-model="item_per_page">
		<option value="10">10</option>
		<option value="20">20</option>
		<option value="30">30</option>
		<option value="40">40</option>
		<option value="50">50</option>
		<option value="60">60</option>
		<option value="70">70</option>
		<option value="80">80</option>
		<option value="90">90</option>
	</select>

</div>