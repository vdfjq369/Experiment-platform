#include <iostream>
#include <stdio.h>
#include <stdlib.h>
#include <string>
#include <string.h>
#include <sstream>
#include <math.h>
#include <ctime>
#include <queue>
#include <map>
#include <fstream>
using namespace std;
 
map<string, string> intersect_road;
map<string, int> closed_nodes_map; //標記確定要走的路線
static int n; // number of routes
//load map to intersect_road<string, string>
void load_map(){
    ifstream fin;
    string intersect_line, sub_str1, sub_str2;
    int index;
    fin.open("GraphTable.txt");
    if(!fin) {
        cout<<"fail to read file."<<endl;
    }
    else{
        intersect_line="", sub_str1="", sub_str2="";
        while(getline(fin, intersect_line)){
            index = intersect_line.find(" -> ");
            sub_str1 = intersect_line.substr(0,index);
            sub_str2 = intersect_line.substr(index+4, intersect_line.length()); //for adjacent node
            
			index = sub_str1.find(" ");
	        sub_str1 = sub_str1.substr(0,index)+","+sub_str1.substr(index+1,sub_str1.length());
			
			intersect_road[sub_str1] = sub_str2;
            closed_nodes_map[sub_str1]=0;
            //open_nodes_map[sub_str1]=0;
        }
		fin.close();
        cout<<"Finish reading file."<<endl;
    }
}

class node
{

    double xPos, yPos;// current position

    double level;// total distance already travelled to reach the node

    double priority;// priority=level+remaining distance estimate (smaller: higher priority)

    node* next;

    node* prev;

    string road_infor;
	
	bool  candidate_intersec;
    public:
        node(double xp, double yp, double d, double p)
            {xPos=xp; yPos=yp; level=d; priority=p;}

        double getxPos() const {return xPos;}
        double getyPos() const {return yPos;}
        double getLevel() const {return level;}
        double getPriority() const {return priority;}
        void setNext(node* next_node){next = next_node;}
        void setPrev(node* prev_node){prev = prev_node;}
        void setPath(string infor){road_infor = infor;}
        node* getNext()const {return next;}
        node* getPrev()const {return prev;}
        string getPath() const {return road_infor;}
		void setCandidateIntersect(bool isCandidate){candidate_intersec=isCandidate;}
		bool getCandidateIntersect(){return candidate_intersec;}
		
        void updatePriority(const double & xDest, const double & yDest)
        {
             priority = level + estimate(xDest, yDest); //A*
             //cout<<"updatePriority success: "<<priority<<endl;
             //printf("updatePriority success:%.15g\n", priority);
        }

        // give better priority to going strait instead of diagonally
        void nextLevel(const double & xVisist, const double & yVisist, const double & xPrev, const double & yPrev)
        {

             double visistDist, toVisistX, toVisistY;
             toVisistX = xVisist - xPrev;
             toVisistY = yVisist - yPrev;
             visistDist = (sqrt(toVisistX*toVisistX + toVisistY*toVisistY));
             level += visistDist;
             //cout<<"nextLevel success: "<<level<<endl;
             //printf("nextLevel success:%.15g\n", level);
        }

        // Estimation function for the remaining distance to the goal.
        const double & estimate(const double & xDest, const double & yDest) const
        {
            static double toDestX, toDestY, destDist;
            toDestX=xDest-xPos;
            toDestY=yDest-yPos;
            destDist = static_cast<double>(sqrt(toDestX*toDestX + toDestY*toDestY));
            //cout<<"estimate success: "<<destDist<<endl;
            return(destDist);
        }
};

bool operator<(const node & a, const node & b)
{
  return a.getPriority() > b.getPriority();
}

static priority_queue<node> pq; // list of open (not-yet-tried) nodes
int num=0; //紀錄第幾次開啟txt file
ofstream myfile;
// A-star algorithm.
// The route returned is a string of direction digits.
void pathFind( const double & xStart, const double & yStart,
                 const double & xFinish, const double & yFinish )
{
    cout<<num<<endl;
    static int i=0; // pq index
    static node* n0;
    static node* m0;
    static double x, y, neighborX, neighborY;
    char c_position[]="";
    string position; // 這個string宣告一定要在char後面，不然會有錯誤
    string neighbors;
    string token,neX, neY, nePos, road_name;
    int detour=0;
    node *head, *curr, *tail;
    bool back_for_detour=0, pos_is_intersection=0;

    /*if(num==0)myfile.open("shortest_path.txt");
    else myfile.open("shortest_path.txt",ios::app);*/
	char c_path_num[]="";
    string path_name;
	sprintf(c_path_num,"%d",num);
	path_name=c_path_num;
	path_name = "path"+path_name+"/table_shortest_path_"+path_name+".txt";
	myfile.open(path_name.c_str());

// create the start node=========================================================================================
    n0=new node(xStart, yStart, 0, 0);
    n0->updatePriority(xFinish, yFinish);
    head = curr = n0;
    tail = new node(0,0,0,0);
    head->setNext(tail);
    head->setPrev(NULL);
    tail->setNext(NULL);
    tail->setPrev(head);
    x=n0->getxPos(); y=n0->getyPos();
    //double to string
    sprintf(c_position, "%.10g,%.10g", x, y);
    position = c_position;
    //myfile<<"position:"<<position<<endl;
    // mark it on the closed nodes map
    closed_nodes_map[position]=1;
    // generate moves (child nodes) in all possible directions
    neighbors = intersect_road[position];
    //myfile<<"neighbors: "<<neighbors<<endl;
    stringstream ss(neighbors);
    i=0;
    while (ss >> token)
    {
        if(i%3 == 0){
            neighborX = atof(token.c_str());
            neX = token;
        }
        if(i%3 == 1){
            neighborY = atof(token.c_str());
            neY = token;
        }
        if(i%3 == 2){
            nePos = neX+","+neY;
            if(i==2) 
			{
				curr->setPath(position+","+token);
				road_name = token;
				pos_is_intersection=false;
			}
			else{
				if((token != road_name) && pos_is_intersection==false)
				{
					
						pos_is_intersection=true;
						curr->setCandidateIntersect(pos_is_intersection);
				}
			}
			
            // generate a child node
            if(!closed_nodes_map[nePos]==1)
            {
                m0=new node( neighborX, neighborY, n0->getLevel(), n0->getPriority());
                m0->nextLevel(neighborX, neighborY, n0->getxPos(), n0->getyPos());
                m0->updatePriority(xFinish, yFinish);
                pq.push(*m0);
            }
        }
        i++;
    }
    n0=new node( pq.top().getxPos(), pq.top().getyPos(), pq.top().getLevel(), pq.top().getPriority());
    x=n0->getxPos(); y=n0->getyPos();

    //double to string
    sprintf(c_position, "%.10g,%.10g", x, y);
    position = c_position;
    //myfile<<"position:"<<position<<endl;
//start node 之後的 node ======================================================================================
    // A* search
    while(x!=xFinish || y!=yFinish)
    {
        closed_nodes_map[position]=1;
        while(!pq.empty()) pq.pop();
        neighbors = intersect_road[position];
        //myfile<<"neighbors: "<<neighbors<<endl;
        stringstream ss(neighbors);
        i=0;
        detour=0;
        while (ss >> token)
        {
            if(i%3 == 0){
                neighborX = atof(token.c_str());
                neX = token;
            }
            if(i%3 == 1){
                neighborY = atof(token.c_str());
                neY = token;
            }
            if(i%3 == 2){
                nePos = neX+","+neY;
                if(i==2 && back_for_detour==0) {
                    curr->setNext(n0);
                    n0->setPrev(curr);
                    curr = n0;
                    curr->setNext(tail);
                    tail->setPrev(curr);
                    curr->setPath(position+","+token);
					road_name = token;
					pos_is_intersection=false;
                }
				else if(i>2 && back_for_detour==0){
					if((token != road_name) && pos_is_intersection==false)
					{
							pos_is_intersection=true;
							curr->setCandidateIntersect(pos_is_intersection);
					}
				}
				
                if(!closed_nodes_map[nePos]==1)
                {
                    // generate a child node
                    m0=new node( neighborX, neighborY, n0->getLevel(), n0->getPriority());
                    m0->nextLevel(neighborX, neighborY, n0->getxPos(), n0->getyPos());
                    m0->updatePriority(xFinish, yFinish);
                    pq.push(*m0);

                }
                else{
                    detour++;
                }
            }

            i++;
            //cout<<token<<endl;
        }

        if(detour == i/3){  //無路可走===============================
            //cout<<"detour"<<endl;
            if(curr->getPrev()==NULL){cout<<"no path from start node to end node!"<<endl; return;}
            curr = curr->getPrev();
            curr->setNext(tail);
            tail->setPrev(curr);
            delete n0;
            x=curr->getxPos(); y=curr->getyPos();
            back_for_detour = 1;
        }else{
            n0=new node( pq.top().getxPos(), pq.top().getyPos(), pq.top().getLevel(), pq.top().getPriority());
            x=n0->getxPos(); y=n0->getyPos();
            back_for_detour = 0;

        }

        //double to string
        sprintf(c_position, "%.10g,%.10g", x, y);
        position = c_position;
        //myfile<<"position:"<<position<<endl;

    }

    // quit searching when the goal state is reached if((*n0).estimate(xFinish, yFinish) == 0)
    if(x==xFinish && y==yFinish)
    {
        int t=0;
		double distance_intersec=0, distanceX_intersec=0, distanceY_intersec=0;
        //myfile<<"0"<<endl; //tags of different routing
        // empty the leftover nodes
        while(!pq.empty()) pq.pop();
        curr = head;
        while(curr->getNext()!=NULL){
            //clear closed_nodes_map except first and second nodes
            if(t>1) {
                stringstream ss(curr->getPath());
                i=0;
                position="";
                while(ss >> token){
                    if(i==0) position = token + ",";
                    if(i==1) position = position + token;
                    i++;
                }
                closed_nodes_map[position]=0;
            }
			
			if(curr==head)
			{
				if(curr->getCandidateIntersect()==true)
					myfile<<curr->getPath()<<",intersection,"<<endl;
				else
					myfile<<curr->getPath()<<",TurningPoint,"<<endl;
			}
			else
			{
				
				if(curr->getCandidateIntersect()==true && curr->getPrev()->getCandidateIntersect()==false)
					myfile<<curr->getPath()<<",intersection,"<<endl;
				else if (curr->getCandidateIntersect()==true && curr->getPrev()->getCandidateIntersect()==true)
				{
					distanceX_intersec = curr->getxPos() - curr->getPrev()->getxPos();
					distanceY_intersec = curr->getyPos() - curr->getPrev()->getyPos();
					distance_intersec = sqrt(distanceX_intersec*distanceX_intersec + distanceY_intersec*distanceY_intersec)/0.00001; //單位:公尺
					if(distance_intersec > 40) 
						myfile<<curr->getPath()<<",intersection,"<<endl;
					else myfile<<curr->getPath()<<",TurningPoint,"<<endl;
				}
				else if (curr->getCandidateIntersect()==false)
					myfile<<curr->getPath()<<",TurningPoint,"<<endl;
				
			}
			
			curr=curr->getNext();
            //delete curr->getPrev();
            t++;
        }
		
        delete tail;
        delete n0; //finish node
        //print last node
        sprintf(c_position, "%.10g,%.10g", x, y);
        position = c_position;
        neighbors = intersect_road[position];
        //myfile<<"neighbors: "<<neighbors<<endl;
        stringstream ss(neighbors);
        i=0;
        while (ss >> token)
        {
            if(i==2)
            { //myfile<<position+" "+token<<endl;
                myfile<<position+","+token+",TurningPoint,";
                break;
            }
            i++;
        }


    }
     //myfile.close();

}

int main(int argc, char* argv[])
{
    static double  x_s, y_s, x_f, y_f;
    int i;
    char s_position[]="";
    char f_position[]="";
    string start_pos="", finish_pos="";
    /*x_f = 121.520153;
    y_f = 25.047128;
    x_s = 121.550512;
    y_s = 25.061493;
    n=3;*/
	x_f = atof(argv[3]);
    y_f = atof(argv[4]);
    x_s = atof(argv[1]);
    y_s = atof(argv[2]);
	n = atoi(argv[5]); 
    //double to string
    sprintf(s_position, "%.10g,%.10g", x_s, y_s);
    start_pos = s_position;
    cout<<"start: "<<start_pos<<endl;
    sprintf(f_position, "%.10g,%.10g", x_f, y_f);
    finish_pos = f_position;
    cout<<"finish: "<<finish_pos<<endl;

    intersect_road[start_pos] = "";
    intersect_road[finish_pos] = "";

    load_map();

    if(start_pos==finish_pos) cout<<"error 1: start node equals to end node!";
    else if(intersect_road[start_pos]=="" && intersect_road[finish_pos]=="") cout<<"error 2: no start and finish node in map!";
    else if(intersect_road[start_pos]=="") cout<<"error 3: no start node in map!";
    else if(intersect_road[finish_pos]=="") cout<<"error 4: no finish node in map!";
    else {

        for(num=0; num<n; num++){
            pathFind(x_s, y_s, x_f, y_f);
            //if(num==n-1) myfile<<"1";
            myfile.close();
        }

    }

    //myfile.close();
    //pathFind(121.513658, 25.056961, 121.520153, 25.047128);
    exit(1);
    return(0);
}
