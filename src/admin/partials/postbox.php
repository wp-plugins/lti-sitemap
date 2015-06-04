<?php
/**
 * LTI Sitemap plugin
 *
 * Box that appears in the admin post editing window
 *
 * @see \Lti\Sitemap\Admin::metadata_box
 */
/**
 * @var \Lti\Sitemap\Admin $this
 */
$post_is_news = $this->helper->get_post_meta_key( 'lti_sitemap_post_is_news' );
$is_news = ( !is_null($post_is_news)&&!empty($post_is_news)) ? "checked=checked" : "";
?>

<div id="plsitemap">
	<div class="form-group">
		<div class="input-group">
			<label><?php echo lsmint( 'opt.post_is_news' ); ?>
				<input type="checkbox" name="lti_sitemap_news[post_is_news]"
				       id="post_is_news" <?php echo $is_news ?>/>
			</label>
		</div>
	</div>
	<div class="form-group">
		<label><?php echo lsmint( 'box.news_title' ); ?></label>

		<div class="input-group">
			<input type="text" name="lti_sitemap[news_title]"
			       id="news_title" value="<?php echo lsmopt( 'news_title' ); ?>"/>
			<span class="suggestion"><?php echo lsmint( 'box.hlp.news_title' ); ?></span>
		</div>
	</div>
	<div class="form-group">
		<label for="news_keywords"><?php echo lsmint( 'opt.news_keywords' ); ?></label>

		<div class="input-group">
			<input type="text" name="lti_sitemap[news_keywords]" id="news_keywords"
			       value="<?php echo lsmopt( 'news_keywords' ); ?>"/>
			<?php $kw = lsmopt( 'news_keywords_suggestion' );
			if ( ! is_null( $kw ) && ! empty( $kw ) ):?>
				<span class="suggestion" id="keywords_suggestion_box">
					<?php echo lsmint( 'box.news_keywords_suggestion' ); ?>&nbsp;
					<span id="lti_sitemap_keywords_suggestion"><?php echo lsmopt( 'news_keywords_suggestion' ); ?></span>
					<a onclick="document.getElementById('news_keywords').setAttribute('value',document.getElementById('lti_sitemap_keywords_suggestion').textContent);">
						<?php echo lsmint( 'box.text_copy' ); ?>
					</a>
				</span>
			<?php endif; ?>
		</div>
	</div>
	<div class="form-group">
		<label><?php echo lsmint( 'opt.news_access' ); ?></label>

		<div class="input-group">
			<label>
				<input name="lti_sitemap[news_access_type]"
				       type="radio" <?php echo lsmrad( 'news_access_type',
					'Full' ); ?>
				       value="Full"
				       id="news_access_type_full"
					/><?php echo lsmint( 'opt.news_access_type_full' ); ?>
			</label>
			<label>
				<input name="lti_sitemap[news_access_type]"
				       type="radio" <?php echo lsmrad( 'news_access_type',
					'Subscription' ); ?>
				       value="Subscription"
				       id="news_access_type_subscription"
					/><?php echo lsmint( 'opt.news_access_type_subscription' ); ?>
			</label>
			<label>
				<input name="lti_sitemap[news_access_type]"
				       type="radio" <?php echo lsmrad( 'news_access_type',
					'Registration' ); ?>
				       value="Registration"
				       id="news_access_type_registration"
					/><?php echo lsmint( 'opt.news_access_type_registration' ); ?>
			</label>
		</div>
	</div>
	<div class="form-group">
		<label><?php echo lsmint( 'opt.news_genre' ); ?></label>

		<div class="input-group">
			<label><?php echo lsmint( 'opt.news_genre_press_release' ); ?>
				<input type="checkbox" name="lti_sitemap[news_genre_press_release]"
				       id="news_genre_press_release" <?php echo lsmchk( 'news_genre_press_release' ); ?>/>
			</label>
			<label><?php echo lsmint( 'opt.news_genre_satire' ); ?>
				<input type="checkbox" name="lti_sitemap[news_genre_satire]"
				       id="news_genre_satire" <?php echo lsmchk( 'news_genre_satire' ); ?>/>
			</label>
			<label><?php echo lsmint( 'opt.news_genre_blog' ); ?>
				<input type="checkbox" name="lti_sitemap[news_genre_blog]"
				       id="news_genre_blog" <?php echo lsmchk( 'news_genre_blog' ); ?>/>
			</label>
			<label><?php echo lsmint( 'opt.news_genre_oped' ); ?>
				<input type="checkbox" name="lti_sitemap[news_genre_oped]"
				       id="news_genre_oped" <?php echo lsmchk( 'news_genre_oped' ); ?>/>
			</label>
			<label><?php echo lsmint( 'opt.news_genre_opinion' ); ?>
				<input type="checkbox" name="lti_sitemap[news_genre_opinion]"
				       id="news_genre_opinion" <?php echo lsmchk( 'news_genre_opinion' ); ?>/>
			</label>
			<label><?php echo lsmint( 'opt.news_genre_user_generated' ); ?>
				<input type="checkbox" name="lti_sitemap[news_genre_user_generated]"
				       id="news_genre_user_generated" <?php echo lsmchk( 'news_genre_user_generated' ); ?>/>
			</label>
		</div>
	</div>
	<div class="form-group">
		<label><?php echo lsmint( 'box.news_stock_tickers' ); ?></label>

		<div class="input-group">
			<input type="text" name="lti_sitemap[news_stock_tickers]"
			       id="news_stock_tickers" value="<?php echo lsmopt( 'news_stock_tickers' ); ?>"/>
			<span class="suggestion"><?php echo lsmint( 'box.hlp.news_stock_tickers' ); ?></span>
		</div>
	</div>
</div>
