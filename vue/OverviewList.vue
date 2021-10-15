<template>
    <v-row class="fill-height">
        <v-col
            cols="2"
        >
            <v-card
                class="mx-auto"
                max-width="500"
            >
                <v-list
                    flat
                >
                    <v-list-item-group
                        multiple
                    >
                        <v-list-item
                            :key="0"
                            :value="0"
                        >
                            <v-list-item-content>
                                <v-list-item-title
                                    v-text="$t('page.course.title')"
                                    class="text-h6 active"
                                />
                            </v-list-item-content>
                            <v-list-item-action>
                                <v-checkbox
                                    :value="0"
                                    @change="selectAllCourses"
                                    v-model="allSelected"
                                />
                            </v-list-item-action>
                        </v-list-item>
                        <v-divider />
                        <v-list-item-group
                            multiple
                        >
                            <v-virtual-scroll
                                :items="courses"
                                :item-height="65"
                                height="598"
                            >
                                <template v-slot="{ item }">
                                    <v-list-item
                                        :key="item.id"
                                        :value="item.id"
                                    >
                                        <v-list-item-content>
                                            <v-list-item-title v-text="item.name" />
                                        </v-list-item-content>

                                        <v-list-item-action>
                                            <v-checkbox
                                                :value="item.id"
                                                :color="item.color"
                                                @change="getEventsForCourse()"
                                                v-model="checkedCourses"
                                            />
                                        </v-list-item-action>
                                    </v-list-item>
                                </template>
                            </v-virtual-scroll>
                        </v-list-item-group>
                    </v-list-item-group>
                </v-list>
            </v-card>
        </v-col>
        <v-col
            cols="10"
        >
            <v-sheet height="64">
                <v-toolbar
                    flat
                >
                    <v-btn
                        fab
                        text
                        small
                        color="grey darken-2"
                        @click="prev"
                    >
                        <v-icon small>
                            mdi-chevron-left
                        </v-icon>
                    </v-btn>
                    <v-btn
                        fab
                        text
                        small
                        color="grey darken-2"
                        @click="next"
                    >
                        <v-icon small>
                            mdi-chevron-right
                        </v-icon>
                    </v-btn>
                    <v-toolbar-title v-if="$refs.calendar">
                        {{ $refs.calendar.title }}
                    </v-toolbar-title>
                    <v-spacer />
                    <v-menu
                        bottom
                        right
                    >
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                outlined
                                color="grey darken-2"
                                v-bind="attrs"
                                v-on="on"
                            >
                                <span>{{ typeToLabel[type] }}</span>
                                <v-icon right>
                                    mdi-menu-down
                                </v-icon>
                            </v-btn>
                        </template>
                        <v-list>
                            <v-list-item @click="type = 'day'">
                                <v-list-item-title>Day</v-list-item-title>
                            </v-list-item>
                            <v-list-item @click="type = 'week'">
                                <v-list-item-title>Week</v-list-item-title>
                            </v-list-item>
                            <v-list-item @click="type = 'month'">
                                <v-list-item-title>Month</v-list-item-title>
                            </v-list-item>
                            <v-list-item @click="type = '4day'">
                                <v-list-item-title>4 days</v-list-item-title>
                            </v-list-item>
                        </v-list>
                    </v-menu>
                </v-toolbar>
            </v-sheet>
            <v-sheet height="600">
                <v-calendar
                    ref="calendar"
                    v-model="focus"
                    color="primary"
                    :events="events"
                    :start="calendarStart"
                    :event-color="getEventColor"
                    :type="type"
                    :weekdays="weekday"
                    @click:event="showEvent"
                    @click:more="viewDay"
                    @click:date="viewDay"
                    @change="updateRange"
                />
                <v-menu
                    v-model="selectedOpen"
                    :close-on-content-click="false"
                    :activator="selectedElement"
                    offset-x
                >
                    <v-card
                        color="grey lighten-4"
                        min-width="350px"
                        flat
                    >
                        <v-toolbar
                            :color="selectedEvent.color"
                            dark
                        >
                            <v-toolbar-title v-html="selectedEvent.course_name" />
                            <v-spacer />
                        </v-toolbar>
                        <v-card-text>
                            <form>
                                {{ selectedEvent.details }}
                            </form>
                        </v-card-text>
                        <v-card-actions>
                            <v-btn
                                text
                                color="default"
                                @click="selectedOpen = false"
                            >
                                {{ $t('general.cancel') }}
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-menu>
            </v-sheet>
        </v-col>
    </v-row>
</template>

<style scoped>
.border{
    background-color: white;
}
</style>
<script>
import CourseCalendarProxy from "@/proxies/CourseCalendarProxy";
import { ErrorSnackbar, StandBy } from '@/app-events.js';
import CourseCalendarMixin from '@/mixins/courseCalendar';
import CourseProxy from "../../proxies/CourseProxy";

export default {
    mixins: [ CourseCalendarMixin ],
    components: {
    },
    props: {
        course: {
            type: Object,
            default: () => ({}),
        }
    },
    data: () => ({
        calendar:[],
        courseEvent:[],
        focus: '',
        type: 'month',
        typeToLabel: {
            month: 'Month',
            week: 'Week',
            day: 'Day',
            '4day': '4 Days',
        },
        select: [ 'Vuetify', 'Programming' ],
        items: [
            'Programming',
            'Design',
            'Vue',
            'Vuetify',
        ],
        weekday: [ 1, 2, 3, 4, 5, 6, 0 ],
        selectedEvent: {},
        selectedElement: null,
        selectedOpen: false,
        events: [],
        dialog: false,
        name: null,
        details: null,
        start: null,
        calendarStart: new Date(),
        end: null,
        color: 'yellow',
        courses: [],
        checkedCourses: [],
        allSelected: false,
    }),
    mounted () {
        new CourseProxy().all().then(({ data }) => {
            this.courses = data.data;
        }).catch(({ data }) => {
            this.displayError(data);
        });
    },
    methods: {
        getEventsForCourse() {
            if (this.checkedCourses.length === 0)
            {
                this.events = [];
            }
            else
            {
                new CourseCalendarProxy().getEventsForCourse(this.checkedCourses).then(({ data }) => {
                    this.events = data.data;
                    this.calendarStart = this.events[ 0 ].start;
                }).catch(({ data }) => {
                    this.$eventBus.dispatch(new ErrorSnackbar('Keine Daten vorhanden'));
                    this.events = [];
                }).finally(() => {
                    this.$eventBus.dispatch(new StandBy(false));
                });
            }

        },
        selectAllCourses: function() {
            this.checkedCourses = [];
            if (this.allSelected) {
                for (let i = 0; i < this.courses.length; i++) {
                    const courseRow = this.courses[ i ];
                    this.checkedCourses.push(courseRow.id);
                }
                this.getEventsForCourse();
            }else
            {
                this.events = [];
            }
        },
        viewDay ({ date }) {
            this.focus = date
            this.type = 'day'
        },
        getEventColor (event) {
            return event.color
        },
        setToday () {
            this.focus = ''
        },
        prev () {
            this.$refs.calendar.prev()
        },
        next () {
            this.$refs.calendar.next()
        },
        editEvent (event) {
            this.currentlyEditing = event.id;
        },
        showEvent ({ nativeEvent, event }) {
            const open = () => {
                const eventRowStart = event.start.split(" ");
                const eventRowEnd = event.end.split(" ");
                this.selectedEvent = event
                this.selectedEvent.timestart = eventRowStart[ 1 ];
                this.selectedEvent.timeend = eventRowEnd[ 1 ];
                this.selectedEvent.start_date = eventRowEnd[ 0 ];
                this.selectedElement = nativeEvent.target
                requestAnimationFrame(() => requestAnimationFrame(() => this.selectedOpen = true))
            }

            if (this.selectedOpen) {
                this.selectedOpen = false
                requestAnimationFrame(() => requestAnimationFrame(() => open()))
            } else {
                open()
            }

            nativeEvent.stopPropagation()
        },
        updateRange ({ start, end }) {
            this.start = start
            this.end = end


        },
        rnd (a, b) {
            return Math.floor((b - a + 1) * Math.random()) + a
        },
    },
}
</script>
