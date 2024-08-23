<?php
/**
 * This file is used to get fun questionnaire layout.
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

  $fq_banner_heading = get_field('fq_banner_heading', 'option');
  $fq_banner_sub_heading = get_field('fq_banner_sub_heading', 'option');
  $fq_banner_button_text = get_field('fq_banner_button_text', 'option');
  $fq_congratulations_sub_heading = get_field('fq_congratulations_sub_heading', 'option');
  // $fq_coupon_code_text = get_field('fq_coupon_code_text', 'option');
  // $fq_coupon_code = get_field('fq_coupon_code', 'option');
  $fq_thank_you_sub_heading = get_field('fq_thank_you_sub_heading', 'option');
  ?>
  <div class="supa-quiz">
      <div class="supa-quiz-title">
          <div class="supa-quiz-title-text">
              <?php if (!empty($fq_banner_heading)) { ?>
                  <h2><?php echo esc_html($fq_banner_heading, 'hello-elementor-child'); ?></h2>
              <?php } ?>
              <?php if (!empty($fq_banner_sub_heading)) { ?>
                  <p><?php echo esc_html($fq_banner_sub_heading, 'hello-elementor-child'); ?></p>
              <?php } ?>
          </div>
          <input type="email" placeholder="Enter your email" class="quiz-email" required>
          <?php if (!empty($fq_banner_button_text)) { ?>
              <a href="javascript:void(0);" class="supa-quiz-submit view-all-btn proceed">
                  <svg width="22" height="20" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M20 0.576172C19.6022 0.576172 19.2207 0.734207 18.9393 1.01551C18.658 1.29681 18.5 1.67834 18.5 2.07617V8.92379C18.5 9.45423 18.2893 9.96293 17.9142 10.338C17.5392 10.7131 17.0304 10.9238 16.5 10.9238H5.62135L8.56072 7.98448C8.84203 7.70317 9.00007 7.32163 9.00007 6.9238C9.00007 6.52597 8.84203 6.14443 8.56072 5.86311C8.27941 5.5818 7.89787 5.42376 7.50004 5.42376C7.1022 5.42376 6.72066 5.5818 6.43935 5.86311L0.939366 11.3631C0.80007 11.5024 0.689575 11.6677 0.614188 11.8497C0.538801 12.0317 0.5 12.2268 0.5 12.4238C0.5 12.6208 0.538801 12.8158 0.614188 12.9978C0.689575 13.1798 0.80007 13.3452 0.939366 13.4845L6.43935 18.9845C6.72066 19.2658 7.1022 19.4238 7.50004 19.4238C7.89787 19.4238 8.27941 19.2658 8.56072 18.9845C8.84203 18.7031 9.00007 18.3216 9.00007 17.9238C9.00007 17.5259 8.84203 17.1444 8.56072 16.8631L5.62135 13.9238H16.5C17.1566 13.9238 17.8068 13.7945 18.4134 13.5432C19.0201 13.2919 19.5712 12.9236 20.0355 12.4593C20.4998 11.995 20.8681 11.4438 21.1194 10.8372C21.3707 10.2306 21.5 9.5804 21.5 8.92379V2.07617C21.5 1.67834 21.342 1.29681 21.0607 1.01551C20.7794 0.734207 20.3978 0.576172 20 0.576172Z" fill="#EC4E34"/>
                  </svg>
                  <span><?php echo esc_html($fq_banner_button_text, 'hello-elementor-child'); ?></span>
              </a>
          <?php } ?>
      </div>
      <div class="supa-quiz-questions-section">
          <?php
          $question_counter = 1;
          $step_counter = 1;
          if (have_rows('fq_questions_and_answers', 'option')):
              while (have_rows('fq_questions_and_answers', 'option')): the_row();
                  $step_heading = get_sub_field('step_heading', 'option');
                  ?>
                  <div class="supa-quiz-question" id="step_<?php echo esc_attr($step_counter); ?>">
                      <h2><?php echo esc_html('Step ' . esc_attr($step_counter) . ': ' . esc_html($step_heading)); ?></h2>
                      <?php if (have_rows('steps', 'option')):
                          while (have_rows('steps', 'option')): the_row();
                              $question = get_sub_field('question');
                              ?>
                              <div class="supa-quiz-question-inner" id="question_<?php echo esc_attr($question_counter); ?>">
                                  <h3><span><?php echo esc_html($question_counter); ?></span><?php echo esc_html($question, 'hello-elementor-child'); ?></h3>
                                  <ul class="choices">
                                      <?php if (have_rows('answers')):
                                          while (have_rows('answers')): the_row();
                                              $option = get_sub_field('option', 'option');
                                              $correct_answer = get_sub_field('correct_answer', 'option');
                                              $answer_value = $correct_answer ? "correct" : "incorrect";
                                              ?>
                                              <li>
                                                  <label>
                                                      <input type="radio" name="question<?php echo esc_attr($question_counter); ?>" value="<?php echo esc_attr($option); ?>" data-answeris="<?php echo esc_attr($answer_value); ?>">
                                                      <span><?php echo esc_html($option); ?></span>
                                                  </label>
                                              </li>
                                          <?php endwhile; endif; ?>
                                  </ul>
                              </div>
                              <?php 
                              $question_counter++;
                          endwhile;
                      endif;
                      ?>
                      <div class="next-prev-btns">
                          <?php if ($step_counter > 1) { ?>
                              <button class="supa-quiz-prev" id="prev_question_<?php echo esc_attr($step_counter - 1); ?>"></button>
                          <?php } ?>
                          <button class="supa-quiz-next" id="next_question_<?php echo esc_attr($step_counter + 1); ?>"></button>
                          <button class="supa-quiz-submit-answers" style="display: none;"><?php echo esc_html__('Submit Answers','hello-elementor-child'); ?></button>
                      </div>
                  </div>
                  <?php
                  $step_counter++;
              endwhile;
          endif;
          ?>
      </div>
      <div class="supa-quiz-result">
          <div class="congrats">
              <div class="supa-quiz-result-detail">
                  <img class="result-img" src="/wp-content/uploads/2024/05/Congratulations.png">
                  <?php if (!empty($fq_congratulations_sub_heading)) { ?>
                      <h3><?php echo esc_html($fq_congratulations_sub_heading, 'hello-elementor-child'); ?></h3>
                  <?php } ?>
              </div>
              <div class="winning-code">
                  <p><?php echo esc_html__('The discount coupon code has been sent to your email id. Please check your email for more details.'); ?></p>
              </div>
          </div>
          <div class="thanks">
              <div class="thanks-bg">
                  <img src="/wp-content/uploads/2024/05/thanks-bg.png">
              </div>
              <div class="supa-quiz-result-detail">
                  <img class="result-img" src="/wp-content/uploads/2024/05/thanks.png">
                  <?php if (!empty($fq_thank_you_sub_heading)) { ?>
                      <h3><?php echo esc_html($fq_thank_you_sub_heading, 'hello-elementor-child'); ?></h3>
                  <?php } ?>
              </div>
              <div class="better-luck-text">
                  <img src="/wp-content/uploads/2024/05/Better-luck-next-time.png">
              </div>
          </div>
      </div>
  </div>