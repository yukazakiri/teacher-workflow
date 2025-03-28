# Summary of the DepEd Grading System in the Philippines

The Department of Education (DepEd) in the Philippines plays a central role in shaping the nation's educational landscape, including the establishment and oversight of the grading system within the K to 12 Basic Education Program. A standardized grading system is fundamental to ensuring consistent and equitable evaluation of student performance across the archipelago [1]. This report aims to provide a comprehensive and structured overview of the DepEd grading system, specifically designed to facilitate understanding and processing by artificial intelligence (AI) systems. The subsequent sections will delve into the key components of this system, including the assessment methods, grading periods, calculation procedures, reporting mechanisms, promotion criteria, and the recognition of student achievements.

## Key Components of the DepEd Grading System

The Department of Education's grading system in the Philippines is built upon several fundamental components that collectively define how student learning is evaluated and reported. These components provide a structured framework for educators and offer crucial information for AI systems seeking to analyze educational data.

### Assessment Components

At the core of the DepEd grading system are the methods used to assess student learning. The K to 12 Basic Education Program employs a standard and competency-based approach, primarily relying on three key components for grading learners from Grades 1 to 12 in every quarter: `Written Work`, `Performance Tasks`, and `Quarterly Assessment` [1].

*   **Written Work** typically encompasses activities such as long tests and mid-term examinations designed to evaluate a student's understanding of concepts and their ability to recall information [6].
*   **Performance Tasks**, on the other hand, are broader and more diverse, including projects, performances, group work, and alternative assessments. These tasks aim to allow students to demonstrate their knowledge and skills in practical and applied contexts, often requiring higher-order thinking and the application of learned material in novel situations [2]. In some subjects, the emphasis on Performance Tasks is more pronounced than on Written Work, reflecting the nature of the learning area and the importance of practical demonstration [6].
*   **Quarterly Assessment** is a major summative examination administered at the conclusion of each grading period. This assessment serves to evaluate the student's overall learning and mastery of the curriculum content covered during that quarter [1]. For high school, this Quarterly Assessment is a mandated component of the grading system for each academic term [2].

The consistent use of these three assessment components across all grade levels within the K to 12 program indicates a foundational structure for evaluating learning [1]. However, it is important to note that the relative importance of each component, as reflected in their percentage weights, varies depending on the specific subject area and the educational level of the students [1]. This variation suggests a nuanced approach by DepEd, recognizing that different disciplines and developmental stages might require different emphases on these assessment types to accurately gauge student understanding and skills. For AI systems to effectively process and interpret student performance data, it is crucial not only to identify the scores obtained in each component but also to apply the correct weightage specific to the subject and grade level.

### Grading Periods (Quarters)

The academic year under the DepEd system is structured into distinct grading periods, commonly referred to as quarters [1]. Each of these quarters represents a significant portion of the academic year and contributes to the student's final grade in each subject [1]. While the research snippets do not explicitly state the exact number of quarters in a school year, it is common knowledge within the Philippine educational context that the academic year is typically divided into four quarters. This division allows for regular assessment and reporting of student progress throughout the year.

### Grade Calculation

The calculation of student grades within the DepEd system involves a systematic, multi-step process [3]. This process ensures a degree of standardization in how student performance is translated into the grades reported on their academic records.

1.  **Obtain Raw Scores:** The initial step involves obtaining the raw scores that students achieve in their various assessments [3]. These raw scores represent the direct outcome of a student's performance on individual tests, tasks, and examinations. It is important to note that all assessed activities are graded in percentage terms, with a maximum possible score of 100% and a minimum of 0%, although exceptions might be made for late submissions or other special circumstances [8].
2.  **Convert to Percentage Scores (PS):** Following the collection of raw scores, the next step is the conversion of these scores into percentage scores (PS) for each assessment component. This conversion is achieved using a standard formula: `Percentage Score = (Student's total score / Possible Highest Score) × 100%` [3]. This step of normalization to a common percentage scale is critical as it allows for a uniform comparison of student performance across different assessments and components, regardless of the original total possible points for each task. For AI systems, understanding this step is essential because it signifies that raw scores from diverse sources need to be transformed into a common metric before any aggregation or comparative analysis can be reliably performed.
3.  **Determine Weighted Scores (WS):** The third step involves determining the weighted score (WS) for each assessment component. Each component (`Written Work`, `Performance Tasks`, `Quarterly Assessment`) is assigned a specific percentage weight that varies based on the subject and the grade level [1]. The weighted score for each component is then calculated by multiplying the percentage score obtained by the student in that component by its assigned weight: `Weighted Score = Percentage Score × Weight of Component` [3]. This use of weighted scores underscores the relative importance that DepEd places on different assessment methods in evaluating student learning within specific subjects and grade levels [7]. For AI systems, it is imperative to recognize and apply these varying weights to accurately reflect DepEd's priorities in evaluating student data.
4.  **Sum Weighted Scores (Initial Grade):** After calculating the weighted scores for each of the three assessment components, the fourth step is to sum these weighted scores together. This sum represents the initial grade of the student for that particular subject in that quarter [3].
5.  **Transmute Initial Grade to Quarterly Grade (QG):** The fifth step introduces a crucial element of the DepEd grading system: the transmutation of the initial grade to a Quarterly Grade (QG). This is done through the use of the DepEd Transmutation Table [3]. The transmutation table is a standardized conversion chart that maps ranges of initial grades to specific transmuted grades, which are then recorded as the Quarterly Grades on the student's report card [9]. This process ensures a consistent reporting of grades across different schools and potentially adjusts for variations in the difficulty of assessments. Notably, the transmutation table sets a floor for reported grades, ensuring that even lower initial grades are raised to a minimum passing grade of 75 on the report card, which corresponds to an initial grade around 60 [2]. This suggests a policy decision to ensure that students who demonstrate even a foundational level of understanding receive a passing mark in their official records. For AI systems analyzing grade data, it is essential to be aware of this transmutation process to accurately interpret the reported grades.
6.  **Calculate Final Grade:** Finally, the sixth step is the determination of the final grade for a learning area. This is calculated by taking the average of the four Quarterly Grades (QG) obtained by the student throughout the academic year: `Final Grade = (1st QG + 2nd QG + 3rd QG + 4th QG) / 4` [6].

### Reporting of Grades

Student grades in the DepEd system are formally reported at the end of each grading period through the issuance of report cards [1]. These report cards serve as official records of a student's academic performance and typically include a list of the subjects taken and the corresponding grades obtained in each [1]. To provide a qualitative understanding of the numerical grades, DepEd employs a specific grading scale that describes different levels of student performance [3]. This scale includes categories such as:

*   **Outstanding** (90-100)
*   **Very Satisfactory** (85-89)
*   **Satisfactory** (80-84)
*   **Fairly Satisfactory** (75-79) - *Passing grades*
*   **Did Not Meet Expectations** (below 75) - *Failing grade*

Teachers often include these descriptive ratings on the report card alongside the numerical grades after the initial grades have been transmuted [6]. This descriptive scale offers a valuable layer of interpretation for AI systems, allowing for categorization and analysis of student performance beyond just the numerical values.

Parents and guardians are typically informed of their child's progress every quarter, often through scheduled meetings with teachers where the report card is discussed [3]. The report card serves as a summary of the student's academic achievements across all learning areas for that particular quarter [3]. In some cases, additional comments regarding the student's performance or areas for improvement might also be provided at the end of each grade level [7].

### Guidelines and Procedures

The Department of Education places a strong emphasis on ensuring consistency and fairness in the evaluation of student performance across all schools in the Philippines. To achieve this, DepEd provides specific guidelines and procedures for the implementation of the grading system [1]. A key document outlining these policies is **DepEd Order No. 8, series of 2015**, titled "Policy Guidelines on Classroom Assessment for the K to 12 Basic Education Program" [4]. This order establishes a unified framework for classroom assessment within the K to 12 curriculum, aiming to standardize assessment practices and ensure equity in measuring student achievement [4]. The objectives of DepEd Order No. 8 include:

*   Providing a clear and coherent assessment system.
*   Promoting the use of formative assessments to guide instruction.
*   Ensuring that all assessments are aligned with national learning standards [4].

For AI systems seeking a comprehensive understanding of the DepEd grading system, this order serves as a primary and authoritative source of information regarding the official regulations and principles underpinning the evaluation of student learning.

### Examples and Illustrations

Various educational resources provide examples and illustrations of how grades are calculated and reported under the DepEd system [1]. These practical examples can be invaluable for understanding the step-by-step application of the grading procedures, from obtaining raw scores to the final reporting of Quarterly Grades.

### Recent Updates or Changes

The DepEd grading system, like any educational policy, is subject to potential updates and changes over time. While the provided research material does not explicitly detail any major recent changes to the core mechanics of the system, it is important for AI systems to be aware of the need to stay informed about the latest issuances and guidelines from DepEd. The fact that some of the browsed URLs have last updated dates in late 2024 and early 2025 [2] indicates that the system is actively being maintained and that users, including AI systems, should seek the most current official information.

## Weightage of Assessment Components

The percentage weights assigned to `Written Work`, `Performance Tasks`, and `Quarterly Assessment` are critical for understanding how different aspects of student learning contribute to the final grade. These weights vary depending on the grade level and the subject area, reflecting DepEd's pedagogical priorities.

**Table 1: Weightage for Grades 1 to 10**

| Subject Area      | Written Work | Performance Tasks | Quarterly Assessment |
| :---------------- | :----------- | :---------------- | :----------------- |
| Languages/AP/EsP  | 30-40% [2]   | 50-60% [2]        | 20% [2]            |
| Science/Math      | 40-50% [2]   | 40-50% [2]        | 20% [2]            |
| MAPEH/EPP/TLE     | 20-30% [2]   | 60-70% [2]        | 20% [2]            |

*Note: The reported percentage weights for Grades 1-10 show some variation across different sources [2]. For instance, the weight for Written Work in Languages/AP/EsP is cited as both 30% and 40%. These discrepancies could stem from updates in DepEd guidelines over time or differences in the interpretation or level of detail provided by various educational websites. AI systems should be prepared to handle such variations and ideally prioritize information from the most recent official DepEd publications to ensure accuracy.*

**Table 2: Weightage for Senior High School (Grades 11 and 12)**

| Subject Track                                                    | Written Work | Performance Tasks | Quarterly Assessment |
| :--------------------------------------------------------------- | :----------- | :---------------- | :----------------- |
| Core Subjects                                                    | 25-40% [2]   | 50-60% [2]        | 25% [2]            |
| Academic Track (All other subjects)                              | 25-40% [2]   | 45-60% [2]        | 25-30% [2]         |
| Academic Track (Research, Work immersion, Performance, Exhibits) | 35-50% [2]   | 40-50% [2]        | 20-25% [2]         |
| TVL/Arts & Design Track, Sports (All other subjects)             | 20-30% [2]   | 60-70% [2]        | 20% [2]            |
| TVL/Arts & Design Track, Sports (Research, Work immersion, Performance, Exhibits) | 20-30% [2]   | 60-70% [2]        | 20% [2]            |

*Note: The weight distribution for Senior High School is more complex due to the various academic and technical-vocational tracks [2]. Similar to Grades 1-10, there are variations in the reported weights across sources. AI systems analyzing SHS grades must have a detailed understanding of the specific subject track to apply the correct weighting scheme and should ideally rely on official DepEd guidelines for the relevant academic year. Across both grade levels, a general trend indicates a significant weightage for Performance Tasks, suggesting DepEd's emphasis on assessing the practical application of knowledge and skills [2].*

## The DepEd Transmutation Table

The DepEd Transmutation Table serves as a crucial tool for converting the initial grade (the sum of weighted scores) into a transmuted Quarterly Grade (QG) for reporting purposes [3]. This table ensures a standardized reporting of grades and also implements a policy where even lower initial grades are raised to a minimum passing grade of 75 on the report card, corresponding to an initial grade around 60 [2]. The table spans from an initial grade of 100 down to 0-3.99, which is transmuted to 60 [9].

**Table 3: Sample DepEd Transmutation Table**

| Initial Grade | Transmuted Grade | Initial Grade | Transmuted Grade |
| :------------ | :--------------- | :------------ | :--------------- |
| 100           | 100              | 66.40 – 67.98 | 79               |
| 98.40 – 99.99 | 99               | 64.81 – 66.39 | 78               |
| 96.80 – 98.39 | 98               | 63.21 – 64.79 | 77               |
| 95.21 – 96.79 | 97               | 61.60 – 63.19 | 76               |
| 93.60 – 95.19 | 96               | 60.01 – 61.59 | 75               |
| 92.00 – 93.59 | 95               | 56.00 – 59.99 | 74               |
| 90.40 – 91.99 | 94               | 52.01 – 55.99 | 73               |
| 88.80 – 90.39 | 93               | 48.00 – 51.99 | 72               |
| 87.20 – 88.79 | 92               | 44.00 – 47.99 | 71               |
| 85.60 – 87.19 | 91               | 40.01 – 43.99 | 70               |
| 84.00 – 85.59 | 90               | 36.00 – 39.99 | 69               |
| 82.40 – 83.99 | 89               | 32.00 – 35.99 | 68               |
| 80.80 – 82.39 | 88               | 28.00 – 31.99 | 67               |
| 79.20 – 80.79 | 87               | 24.00 – 27.99 | 66               |
| 77.60 – 79.19 | 86               | 20.00 – 23.99 | 65               |
| 76.00 – 77.59 | 85               | 16.00 – 19.99 | 64               |
| 74.40 – 75.99 | 84               | 12.00 -15.99  | 63               |
| 72.80 – 74.38 | 83               | 8.00 – 11.99  | 62               |
| 71.20 – 72.79 | 82               | 4.00 – 7.99   | 61               |
| 69.61 – 71.19 | 81               | 0 – 3.99      | 60               |
| 68.00 – 69.59 | 80               |               |                  |

*Note: The transmutation table's effect of raising lower initial grades to a passing mark suggests a policy aimed at ensuring a minimum level of reported achievement. AI systems analyzing historical grade data should be mindful of this floor effect, as it might influence statistical distributions and comparisons. Furthermore, the existence of the transmutation process indicates that the initial grades might offer a more granular view of student performance, while the transmuted grades are used for official reporting and decisions regarding promotion.*

## Reporting Student Performance

The performance of students in the DepEd system is reported using a specific grading scale that provides both numerical ranges and qualitative descriptions [3].

**Table 4: DepEd Grading Scale**

| Grade Range | Performance Level         |
| :---------- | :------------------------ |
| 90-100      | Outstanding               |
| 85-89       | Very Satisfactory (Passed) |
| 80-84       | Satisfactory (Passed)      |
| 75-79       | Fairly Satisfactory (Passed)|
| Below 75    | Did Not Meet Expectations (Failed) |

Parents and guardians receive updates on their child's progress every quarter, often through discussions with teachers regarding the report card [3]. The report card provides a summary of the student's performance across all subjects for that grading period [3], and may also include additional comments on their overall progress [7].

## Promotion and Retention Criteria

The criteria for promoting students to the next grade level in the DepEd system are based on their academic performance, with specific requirements varying slightly depending on the grade level [2].

*   **Grades 1 to 3:**
    *   Must achieve a Final Grade of at least 75 in **all** learning areas to be promoted [2].
    *   Grade below 75 in up to **two** subjects: Required to attend remedial classes; successful completion is necessary for promotion, otherwise retained [2].
    *   Fail in **three or more** subjects: Retained in the same grade level [2].
*   **Grades 4 to 10:**
    *   Must attain a Final Grade of at least 75 in **all** subjects [2].
    *   Grade below 75 in up to **two** subjects: Must participate in remedial classes; promotion contingent on passing these classes [2].
    *   Fail in **three or more** subjects: Remain in the same grade [2].
    *   Must pass all Elementary learning areas for Elementary Certificate [2].
    *   Must clear all Junior High School learning areas for JHS Certificate and promotion to SHS [2].
*   **Grades 11 and 12 (Senior High School):**
    *   Need a Final Grade of at least 75 in **all** subjects for a semester to be promoted to the next semester [2].
    *   Not meeting expectations in a prerequisite subject might prevent promotion [2].
    *   Students not meeting expectations in a whole semester still need their Certificate of Senior High School [7].
    *   Remedial classes required for subjects not met; failure to clear results in failing the subject [7].
    *   Must clear **all** subjects in SHS and pass necessary remedial classes for Certificate of Senior High School [2].

These promotion criteria highlight the importance of achieving a passing grade across all subjects, while the provision for remedial classes offers a chance for students with limited failures to catch up and progress. AI systems analyzing student progression data should take these rules into account when modeling academic trajectories.

## Awards and Recognition

The DepEd grading system includes provisions for recognizing students' achievements in various aspects of school life, promoting a holistic view of student development [7].

*   **Academic Excellence Awards:** Given to students (Grades 1-12) who achieve high academic averages and pass all subjects [11].
    *   *With Highest Honors:* Average grade of 98-100 [7].
    *   *With High Honors:* Average grade of 95-97 [7].
    *   *With Honors:* Average grade of 90-94 [7].
*   **Leadership Awards:** Recognize students with strong leadership skills and active participation in school organizations [8].
*   **Special Awards:** Conferred upon students excelling in specific areas (arts, sports, journalism, community service) [8], including those representing the school in competitions [15].
*   **Conduct Awards:** Honor students exhibiting good morals, self-discipline, and respect [8], requiring consistent demonstration of DepEd core values and a high conduct rating [11].
*   **Recognition for Perfect Attendance:** Given to students with no absences during a quarter [11].
*   **Performance Awards (Kindergarten):** Recognize abilities or improvement based on the curriculum [11].
*   **Character Traits Awards (Kindergarten to Grade 3):** Affirm positive behaviors [11].

This comprehensive awards system reflects a move towards recognizing a broader range of student accomplishments beyond just academics. The shift from a competitive ranking system to an honors system based on achieving specific grade thresholds aims to encourage individual excellence and inclusivity [12]. However, there have been discussions regarding the increasing number of honor students and the potential impact of the transmutation table on these awards [12], suggesting a need for AI systems analyzing this data to be aware of potential debates surrounding the reflection of true academic excellence.

## Relevant DepEd Orders

**DepEd Order No. 8, series of 2015**, is a pivotal policy document that provides the guidelines for classroom assessment in the K to 12 Basic Education Program [4]. This order establishes a unified framework to ensure consistency and fairness in evaluating student performance across the country [4]. Its objectives include providing a clear assessment system, promoting formative assessment practices, and aligning assessments with national learning standards [4]. The order outlines different types of assessments, including formative (used to improve learning) and summative (used to evaluate learning), with summative assessment comprising `Written Work`, `Performance Tasks`, and `Quarterly Exams` [10]. For AI systems seeking a deep understanding of the DepEd grading system, DepEd Order No. 8, s. 2015, is a foundational resource that provides the official principles and guidelines governing classroom assessment.

## Conclusion

The DepEd grading system in the Philippines is a comprehensive framework for evaluating and reporting student performance within the K to 12 Basic Education Program. It relies on a combination of `Written Work`, `Performance Tasks`, and `Quarterly Assessments`, with varying weights assigned to these components based on the grade level and subject area. The calculation of grades involves converting raw scores to percentages, applying weights, summing these to an initial grade, and then using a transmutation table to obtain the final Quarterly Grade. Student progress is reported using a descriptive scale, and specific criteria are in place for promotion to the next grade level. The system also includes a range of awards and recognitions to celebrate various aspects of student achievement.

For AI systems to effectively understand and process information related to the DepEd grading system, it is crucial to consider these key components:

*   Specific weights for different subjects and grade levels.
*   Process of grade calculation and transmutation.
*   Reporting scale.
*   Promotion criteria.

Furthermore, understanding the policy guidelines outlined in **DepEd Order No. 8, s. 2015**, is essential for a comprehensive grasp of the principles underlying the system. By taking these elements into account, AI systems can accurately analyze and utilize educational data from the Philippines for tasks such as data analysis, report generation, and educational research, while also being mindful of potential variations in data sources and ongoing discussions surrounding the system's effectiveness.